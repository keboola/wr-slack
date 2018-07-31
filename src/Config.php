<?php

declare(strict_types=1);

namespace Keboola\SlackWriter;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public function getToken() : string
    {
        return $this->getValue(['parameters', '#token']);
    }

    public function getChannel() : string
    {
        return $this->getValue(['parameters', 'channel']);
    }
}
