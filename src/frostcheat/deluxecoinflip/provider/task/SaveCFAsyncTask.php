<?php

namespace frostcheat\deluxecoinflip\provider\task;

use pocketmine\scheduler\AsyncTask;

class SaveCFAsyncTask extends AsyncTask
{
    private string $file;
    private string $data;

    public function __construct(string $file, array $coinflips) {
        $this->file = $file;
        $this->data = yaml_emit($coinflips);
    }

    public function onRun(): void {
        file_put_contents($this->file, $this->data);
    }
}