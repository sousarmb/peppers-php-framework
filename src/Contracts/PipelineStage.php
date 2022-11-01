<?php

namespace Peppers\Contracts;

interface PipelineStage {

    /**
     * 
     * @param mixed $io
     * @return mixed
     */
    public function run(mixed $io): mixed;
}
