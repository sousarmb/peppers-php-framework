<?php

namespace Peppers\Contracts;

use Peppers\Contracts\PipelineStage;

interface Pipeline extends PipelineStage {

    /**
     * 
     * @param PipelineStage $io
     * @return self
     */
    public function add(PipelineStage $io): self;
}
