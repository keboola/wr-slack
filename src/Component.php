<?php

declare(strict_types=1);

namespace Keboola\SlackWriter;

use Keboola\Component\BaseComponent;
use Keboola\Component\UserException;
use Keboola\Csv\CsvReader;

class Component extends BaseComponent
{
    public function run(): void
    {
        /** @var Config $config */
        $config = $this->getConfig();
        $writer = new Writer($config->getToken(), $this->getLogger());
        $tables = $config->getInputTables();
        if (count($tables) < 1) {
            throw new UserException(
                "At least one table must be supplied on input. The table must contain one or two columns"
            );
        }
        foreach ($tables as $table) {
            $csvFile = $this->getDataDir() . '/in/tables/' . $table['destination'];
            $csv = new CsvReader(
                $csvFile,
                CsvReader::DEFAULT_DELIMITER,
                CsvReader::DEFAULT_ENCLOSURE,
                CsvReader::DEFAULT_ESCAPED_BY,
                1
            );
            $header = $csv->getHeader();
            if (count($header) > 2) {
                throw new UserException(
                    sprintf(
                        'The table "%s" contains %s columns. Every table must contain at most 2 columns.',
                        $table['source'],
                        count($header)
                    )
                );
            }
            $cnt = 0;
            foreach ($csv as $row) {
                $writer->writeMessage($config->getChannel(), $row[0], $row[1] ?? null);
                $cnt++;
            }
            $this->getLogger()->info(sprintf('Sent %s messages for table "%s"', $cnt, $table['source']));
        }
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }
}
