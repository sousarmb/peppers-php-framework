<?php

namespace Peppers;

use Closure;
use RuntimeException;
use Peppers\Contracts\Pipeline as PipelineContract;
use Peppers\Contracts\PipelineStage;
use Peppers\Factory;

class Pipeline implements PipelineContract {

    private bool $_complete = false;
    private int $_lastRan;
    private bool $_running = false;
    private bool $_setup = false;
    private int $_stageCount = 0;
    private array $_stages = [];

    /**
     *
     * @param array $stages
     */
    public function __construct(
            array $stages = []
    ) {
        $this->_stages = $stages;
        $this->_stageCount = count($stages);
    }

    /**
     * 
     * @param PipelineStage $stage
     * @return self
     * @throws RuntimeException
     */
    public function add(PipelineStage $stage): self {
        if ($this->_running) {
            throw new RuntimeException('Cannot add pipeline stages when running');
        }

        $this->_stages[] = $stage;
        $this->_stageCount++;
        return $this;
    }

    /**
     * 
     * @param string $stage
     * @return PipelineStage
     * @throws RuntimeException
     */
    public function remove(string $stage): PipelineStage {
        if ($this->_running) {
            throw new RuntimeException('Cannot remove pipeline stage when running');
        }

        $current = reset($this->_stages);
        do {
            if ($current instanceof $stage) {
                return array_splice($this->_stages, key($this->_stages), 1);
            }
        } while ($current = next($this->_stages));
        throw new RuntimeException("Pipeline stage $stage not found");
    }

    /**
     * 
     * @param mixed $io
     * @return mixed
     * @throws StrategyFail
     */
    public function run(mixed $io): mixed {
        if (!$this->_setup) {
            $this->setup();
        }

        $this->_complete = !$this->_running = true;
        foreach ($this->_stages as $k => $stage) {
            $this->_lastRan = $k;
            $io = $stage->run($io);
        }
        $this->_complete = !$this->_running = false;
        return $io;
    }

    /**
     * 
     * @return int
     */
    public function getStageCount(): int {
        return $this->_stageCount;
    }

    /**
     * 
     * @return void
     * @throws RuntimeException
     */
    private function setup(): void {
        if ($this->_setup) {
            return;
        }
        /* get the stage classes and check the classes implement PipelineStage 
         * interface */
        foreach ($this->_stages as $key => &$stage) {
            if (is_string($stage) || ($stage instanceof Closure)) {
                $class = Factory::getClassInstance($stage);
            }
            if ($class instanceof PipelineStage) {
                $stage = $class;
            } else {
                throw new RuntimeException("Invalid pipeline stage found at $key position");
            }
        }
        $this->_setup = !$this->_setup;
    }

    /**
     * 
     * @return PipelineStage
     * @throws RuntimeException
     */
    public function getLastRunStage(): PipelineStage {
        if (!$this->_setup || !isset($this->_lastRan)) {
            throw new RuntimeException('Pipeline has not run');
        }

        return $this->_stages[$this->_lastRan];
    }

    /**
     * 
     * @return bool
     * @throws RuntimeException
     */
    public function runCompleted(): bool {
        if (!$this->_setup) {
            throw new RuntimeException('Pipeline has not run');
        }

        return $this->_complete;
    }

}
