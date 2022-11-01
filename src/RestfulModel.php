<?php

namespace Peppers;

use Generator;
use Peppers\Contracts\DefaultMethod;
use Peppers\Contracts\Model;
use Peppers\Contracts\ModelRepository;
use Peppers\Exceptions\BadRequest;
use Peppers\Exceptions\CannotRespondWithAcceptedContentType;
use Peppers\Helpers\Http\ContentNegotiation;
use Peppers\Helpers\Http\Request\BodyParameter;
use Peppers\Helpers\Http\Request\QueryParameter;
use Peppers\Helpers\Sql\Conditions;
use Peppers\Helpers\Sql\DataPromise;
use Peppers\Helpers\Types\Operator;
use Peppers\Helpers\Xml;
use Peppers\Response;
use Peppers\RouteRegister;
use Peppers\ServiceLocator;
use Peppers\Contracts\RouteResolver;
use PDOException;
use RuntimeException;
use SimpleXMLElement;

class RestfulModel implements DefaultMethod {

    private Model $_model;
    private string $_requestMethod;
    private ModelRepository $_repository;
    private string $_responseType;

    /**
     * 
     * @param ModelRepository $repository
     * @param RouteRegister $routeRegister
     */
    public function __construct(
            ModelRepository $repository,
            RouteRegister $routeRegister
    ) {
        $this->_repository = $repository;
        $this->_requestMethod = $routeRegister->getHttpMethod();
    }

    /**
     * 
     * @return Response
     */
    public function default(): Response {
        try {
            $this->_responseType = $this->contentTypeOrFailFast();
        } catch (CannotRespondWithAcceptedContentType $e) {
            return (new Response())->setStatusCode($e->getCode());
        }
        $this->_model = new ($this->_repository->getModelClass())();
        switch ($this->_requestMethod) {
            case 'DELETE':
                return $this->delete();

            case 'GET':
                return $this->get();

            case 'HEAD':
                return $this->head();

            case 'POST':
                return $this->post();

            default: /* PATCH, PUT, ... */
                return (new Response())->setStatusCode(405);
        }
    }

    /**
     * 
     * @return string
     * @throws CannotRespondWithAcceptedContentType
     */
    private function contentTypeOrFailFast(): string {
        $content = new ContentNegotiation();
        if ($content->clientAccepts('application/json')) {
            $responseType = 'application/json';
        } elseif ($content->clientAccepts('application/xml')) {
            $responseType = 'application/xml';
        } elseif ($content->clientAccepts('text/plain') || $content->clientAccepts('*/*')) {
            $responseType = 'text/plain';
        } else {
            throw new CannotRespondWithAcceptedContentType();
        }

        return $responseType;
    }

    /**
     * 
     * @return array|null
     */
    private function getPrimaryKeyFromUrl(): ?array {
        $resolver = ServiceLocator::get(RouteResolver::class);
        $resolver instanceof RouteResolver;
        if (!$resolver->getResolved()->getHasPathRegex()) {
            return null;
        }

        return array_map(
                function ($primaryKey) use ($resolver) {
                    return $resolver->getResolvedPathValue($primaryKey);
                },
                $this->_model->getPrimaryKeyColumns()
        );
    }

    /**
     * 
     * @return Response
     */
    private function get(): Response {
        $response = new Response();
        $primaryKeyValues = $this->getPrimaryKeyFromUrl();
        if (!$primaryKeyValues || in_array(null, $primaryKeyValues)) {
            // no primary key sent? check if this is a more general query
            $model = $this->getFromFreeQuery();
            if (is_null($model)) {
                // missing query parameters
                return $response->setStatusCode(400);
            }
        } elseif (!($model = $this->_repository->findByPrimaryKey($primaryKeyValues))) {
            // model not found
            return $response->setStatusCode(404);
        }
        switch ($this->_responseType) {
            case 'application/json':
                $response->json($model);
                break;
            case 'application/xml':
                $response->xml($this->getXmlDocumentFromModel($model));
                break;
            case 'text/plain':
            default:
                $this->getPlainTextFromModel($model, $response);
                break;
        }
        return $response;
    }

    /**
     * 
     * @return Generator|null
     */
    private function getFromFreeQuery(): ?Generator {
        $conditions = new Conditions();
        $promise = $this->_repository->findByCondition();
        $timestampColumns = ['created_on', 'deleted_on', 'updated_on'];
        foreach ($this->_model->getModelColumns() as $query) {
            $value = (new QueryParameter($query))->getValue();
            if (!is_null($value)) {
                in_array($query, $timestampColumns) ? $this->parseTimestampQuery(
                                        $query,
                                        $value,
                                        $conditions,
                                        $promise
                                ) : $conditions->where($query, Operator::eq, $value);
            }
        }
        if ($conditions->hasConditions()) {
            return $this->_repository->findByCondition()
                            ->select($this->_model->getModelColumns())
                            ->where($conditions)
                            ->resolve();
        }
        // no query to be made
        return null;
    }

    /**
     * 
     * @param string $query
     * @param string $value
     * @param Conditions $conditions
     * @param DataPromise $promise
     * @return void
     */
    private function parseTimestampQuery(
            string $query,
            string $value,
            Conditions $conditions,
            DataPromise $promise
    ): void {
        $isInterval = explode('|', $value);
        if (count($isInterval) > 1) {
            list($startDate, $endDate) = $isInterval;
            $conditions->between($query, $startDate, $endDate);
        } elseif (strpos($value, ':') === false) {
            $conditions->function("DATE($query)", Operator::eq, $value);
        } else {
            $conditions->function($query, Operator::eq, $value);
        }
        if ($query == 'deleted_on') {
            $promise->withDeleted();
        }
    }

    /**
     * 
     * @param Generator|Model $modelCollection
     * @param Response $response
     * @return void
     */
    private function getPlainTextFromModel(
            Generator|Model $modelCollection,
            Response $response
    ): void {
        /* output up to 2MB is kept in memory, if it gets bigger automatically 
         * write to a temporary file */
        $csv = fopen(
                'php://temp/maxmemory:' . (2 * 1024 * 1024),
                'r+'
        );
        if ($modelCollection instanceof Generator) {
            $first = true;
            foreach ($modelCollection as $modelInstance) {
                if ($first) {
                    $first = !$first;
                    fputcsv($csv, $modelInstance->getModelColumns());
                }
                fputcsv($csv,
                        array_map(
                                fn($value) => is_null($value) ? '' : $value,
                                $modelInstance->toArray(false)
                        )
                );
            }
        } else {
            fputcsv($csv, $modelCollection->getModelColumns());
            fputcsv($csv,
                    array_map(
                            fn($value) => is_null($value) ? '' : $value,
                            $modelCollection->toArray(false)
                    )
            );
        }
        // prepare to read
        rewind($csv);
        // put it in the response
        $response->plainText(stream_get_contents($csv));
        // housekeeping
        fclose($csv);
    }

    /**
     * 
     * @param Generator|Model $modelCollection
     * @return SimpleXMLElement
     */
    private function getXmlDocumentFromModel(Generator|Model $modelCollection): SimpleXMLElement {
        $modelData = Xml::getSimpleEmptyDocument($this->_repository->getModelClass(true));
        if ($modelCollection instanceof Generator) {
            foreach ($modelCollection as $modelInstance) {
                $child = $modelData->addChild(
                        $modelInstance->getPrimaryKey(true, '|')
                );
                foreach ($modelInstance as $key => $value) {
                    $child->addChild($key, $value);
                }
            }
        } else {
            foreach ($modelCollection as $key => $value) {
                $modelData->addChild($key, $value);
            }
        }
        return $modelData;
    }

    /**
     * 
     * @return Response
     */
    private function head(): Response {
        return $this->get();
    }

    /**
     * 
     * @return Response
     * @throws RuntimeException
     */
    private function post(): Response {
        $response = new Response();
        $primaryKeyValues = $this->getPrimaryKeyFromUrl();
        if (!$primaryKeyValues || in_array(null, $primaryKeyValues)) {
            // primary key value missing, create new model instance
            $model = clone $this->_model;
            $this->_repository->pushNew($model);
            // FALSE = create model
            $createOrUpdate = false;
        } elseif (($model = $this->_repository->findByPrimaryKey($primaryKeyValues))) {
            // TRUE = update model
            // primary key value(s) sent, got model instance to update
            $createOrUpdate = true;
        } else {
            return $response->setStatusCode(404);
        }

        try {
            $this->createOrUpdateModel($createOrUpdate, $model);
            if ($createOrUpdate) {
                if (!$model->hasDirtyData()) {
                    // there is no data to update
                    return $response->setStatusCode(204);
                }

                $count = $this->_repository->flushUpdates();
                $response->setStatusCode(204);
            } else {
                $count = $this->_repository->flushCreates();
                $response->setStatusCode(201);
            }
        } catch (BadRequest $e) {
            return $response->setStatusCode(400);
        } catch (PDOException $e) {
            $response->setStatusCode(400);
            switch ($this->_responseType) {
                case 'application/json':
                    $message = ['Exception' => $e->getMessage()];
                    return $response->json($message);

                case 'application/xml':
                    $document = Xml::getSimpleEmptyDocument('Exception');
                    $document[0] = $e->getMessage();
                    return $response->xml($document);

                case 'text/plain':
                default:
                    return $response->plainText('Exception: ' . $e->getMessage());
            }
        }
        if ($count !== 1) {
            $message = 'Could not ' . ($createOrUpdate ? 'update' : 'create') . ' model: [%s] [%s] %s';
            throw new RuntimeException(
                            vsprintf($message,
                                    $this->_repository->getConnection()->errorInfo()
                            ),
                            500
            );
        }

        return $response;
    }

    /**
     * 
     * @param bool $createOrUpdate
     * @param Model $model
     * @return void
     * @throws BadRequest
     */
    private function createOrUpdateModel(
            bool $createOrUpdate,
            Model $model
    ): void {
        if ($createOrUpdate) {
            // update the model
            foreach ($model->getUnprotectedColumns() as $column) {
                $model->$column = (new BodyParameter($column))->getValue();
            }
        } else {
            // create the model, check for primary key values first
            foreach ($model->getPrimaryKeyColumns() as $column) {
                $value = (new BodyParameter($column))->getValue();
                if (is_null($value)) {
                    // cannot create
                    throw new BadRequest();
                }

                $model->$column = $value;
            }
            // and the remaining columns
            foreach (array_diff(
                    $model->getUnprotectedColumns(),
                    $model->getPrimaryKeyColumns()
            ) as $column) {
                $value = (new BodyParameter($column))->getValue();
            }
        }
    }

    /**
     * 
     * @param Model $model
     * @return Response
     */
    private function delete(): Response {
        $response = new Response();
        $primaryKeyValues = $this->getPrimaryKeyFromUrl();
        if (!$primaryKeyValues || in_array(null, $primaryKeyValues)) {
            // primary key value missing, abort
            return $response->setStatusCode(400);
        }

        $rowCount = $this->_repository->deleteByPrimaryKey($primaryKeyValues);
        if ($rowCount) {
            // model soft deleted
            return $response->setStatusCode(204);
        }

        return $response->setStatusCode(404);
    }

}
