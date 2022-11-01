<?php

namespace Peppers;

use App\Events\LogRequestEvent;
use DateTime;
use Peppers\Base\Strategy;
use Peppers\Exceptions\CannotRespondWithAcceptedContentType;
use Peppers\Exceptions\StrategyFail;
use Peppers\Helpers\ResponseSent;
use Peppers\Factory;
use Peppers\Pipeline;
use Peppers\Contracts\RouteResolver;
use Peppers\ServiceLocator;
use Settings;
use Throwable;

final class Kernel {

    private static bool $_kernelGo = false;
    private static array $_log;
    private static DateTime $_requestTime;
    private static array $_strategy;

    /**
     * 
     * @return void
     */
    private static function getStrategies(): void {
        // read core strategies file
        $strategiesFile = Settings::get('APP_CONFIG_DIR') . 'strategies.php';
        if (is_readable($strategiesFile)) {
            static::$_strategy = include_once $strategiesFile;
            return;
        }
        // kernel panic!
        ob_clean();
        static::panic("Cannot find $strategiesFile");
        static::sendPanicResponse();
        exit;
    }

    /**
     * 
     * @param string $named
     * @return array
     * @throws StrategyFail
     */
    private static function checkStrategies(string $named): array {
        $classes = array_map(
                function ($implementation) {
                    return Factory::getClassInstance($implementation);
                },
                static::$_strategy[$named]
        );
        $validStrategies = array_map(
                function ($implementation) {
                    return $implementation instanceof Strategy;
                },
                $classes
        );
        if (false !== ($position = array_search(false, $validStrategies, true))) {
            throw new StrategyFail(static::$_strategy[$named][$position]);
        }

        return $classes;
    }

    /**
     * 
     * @param array $classes
     * @return void
     * @throws StrategyFail
     */
    private static function runStrategies(array $classes): void {
        $results = array_map(
                function ($implementation) {
                    return $implementation->default();
                },
                $classes
        );
        foreach ($results as $k => $result) {
            if (!$result && !$classes[$k]->allowedToFail()) {
                throw new StrategyFail($classes[$k]::class);
            }
        }
    }

    /**
     * Runs the boot strategies
     * 
     * @return void
     */
    private static function boot(): void {
        /* load the strategies file, that'll have userland code for serving
         * the response back to the user agent */
        static::getStrategies();
        try {
            // boot up the framework core services
            static::runStrategies(
                    static::checkStrategies('boot')
            );
            // check shutdown strategy as well
            static::$_strategy['shutdown'] = static::checkStrategies('shutdown');
        } catch (Throwable $t) {
            ob_clean();
            static::panic($t);
            static::sendPanicResponse();
            exit;
        }
    }

    /**
     * Run the application
     * 
     * @return void
     * @throws CannotRespondWithAcceptedContentType
     */
    public static function go(): void {
        static::$_requestTime = new DateTime('now');
        if (Settings::get('LOG_KERNEL_FLOW')) {
            static::log('Kernel::go()');
        }
        /* load the strategies file, that'll have the list of classes, 
         * including the ones that contain developer code for serving the 
         * response back to the user agent */
        static::boot();
        if (Settings::get('LOG_KERNEL_FLOW')) {
            static::log('Booted');
        }
        // if all is well this the strategies move forward 
        try {
            // get the request handler
            $route = ServiceLocator::get(RouteResolver::class)->resolve();
            // start processing the request
            if (is_null($route)) {
                // cannot serve response
                http_response_code(404);
            } else {
                $pipeline = new Pipeline(static::$_strategy['requestResponse']);
                $handled = $pipeline->run($route);
                if (Settings::get('LOG_KERNEL_FLOW')) {
                    static::log('Request/Response');
                }
                if (!($handled instanceof ResponseSent)) {
                    throw new CannotRespondWithAcceptedContentType();
                }
            }
        } catch (Throwable $t) {
            static::log('Throwable caught in Kernel', [
                'message' => $t->getMessage(),
                'code' => $t->getCode(),
                'trace' => $t->getTrace()
            ]);
            $exceptionHandling = new Pipeline(static::$_strategy['exceptionHandling']);
            $exceptionHandling->run($t);
        }
        if (Settings::get('LOG_KERNEL_FLOW')) {
            static::log('Kernel request/response finished');
        }
        if (($log = static::getLog())) {
            Factory::getClassInstance(LogRequestEvent::class)
                    ->setIsDeferred(false)
                    ->setData(static::getLog())
                    ->dispatch();
        }
        // shutdown the framework
        static::shutdown();
    }

    /**
     * Runs the shutdown strategies
     * 
     * @return void
     */
    private static function shutdown(): void {
        try {
            static::runStrategies(static::$_strategy['shutdown']);
        } catch (Throwable $t) {
            ob_clean();
            // nothing should come this far at this stage ...
            static::panic($t);
        }
    }

    /**
     * Writes to log file
     * 
     * @param string $message
     * @param array $extraData
     * @return void
     */
    public static function log(
            string $message,
            array $extraData = []
    ): void {
        /* 1st log entry? show human readable date with tz else just the time 
         * difference to the start of the request */
        static::$_log[] = [
            static::$_kernelGo ? (new DateTime('now'))->diff(static::$_requestTime) : (new DateTime('now'))->format('c'),
            trim($message),
            $extraData
        ];
        // 1st log entry? not anymore ;)
        static::$_kernelGo = static::$_kernelGo ?: !static::$_kernelGo;
    }

    /**
     * Gets the Kernel log file
     * 
     * @return array|null
     */
    public static function getLog(): ?array {
        return isset(static::$_log) ? static::$_log : null;
    }

    /**
     * Writes to Kernel panic log file
     * 
     * @param array|Throwable|string $panic
     * @return void
     */
    public static function panic(array|Throwable|string $panic): void {
        $panicFile = sprintf('%s%s-%s',
                Settings::get('LOGS_DIR'),
                (new DateTime('now'))->format('Ymd'),
                Settings::get('KERNEL_PANIC_FILE')
        );
        $debug = $panic instanceof Throwable ? [
            'message' => $panic->getMessage(),
            'code' => $panic->getCode(),
            'file' => $panic->getFile(),
            'line' => $panic->getLine(),
            'trace' => $panic->getTrace()] : null;
        $message = sprintf('%s %s',
                static::$_requestTime->format('Y-m-d H:i:s'),
                json_encode(['Panic' => $debug ?? $panic])
        );
        $fhandle = fopen($panicFile, 'a');
        fwrite($fhandle, $message . PHP_EOL);
        fclose($fhandle);
    }

    /**
     * 
     * @return void
     */
    private static function sendPanicResponse(): void {
        http_response_code(500);
        header('Content-Type: text/plain');
        echo Settings::get('KERNEL_PANIC_MESSAGE');
    }

}
