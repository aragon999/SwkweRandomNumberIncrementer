<?php

namespace SwkweRandomNumberIncrementer\Services;

use Doctrine\DBAL\Connection;
use Shopware\Components\NumberRangeIncrementerInterface;
use Shopware\Components\NumberRangeIncrementer as DecoratedNumberRangeIncrementer;

class NumberRangeIncrementer implements NumberRangeIncrementerInterface
{
    const INCREMENT_RANDOM_TYPE = 'invoice';

    const INCREMENT_RANDOM_MAX = 9;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var NumberRangeIncrementerInterface
     */
    private $decoratedNumberRangeIncrementer;

    /**
     * @param Connection $connection
     */
    public function __construct(
        DecoratedNumberRangeIncrementer $decoratedNumberRangeIncrementer,
        Connection $connection
    ) {
        $this->decoratedNumberRangeIncrementer = $decoratedNumberRangeIncrementer;
        $this->connection = $connection;
    }
    /**
     * {@inheritdoc}
     */
    public function increment($name)
    {
        if ($name === self::INCREMENT_RANDOM_TYPE) {
            $this->connection->beginTransaction();

            try {
                $number = $this->connection->fetchColumn('SELECT number FROM s_order_number WHERE name = ? FOR UPDATE', [$name]);

                if ($number === false) {
                    throw new \RuntimeException(sprintf('Number range with name "%s" does not exist.', $name));
                }

                $incrementNumber = rand(1, self::INCREMENT_RANDOM_MAX);

                $this->connection->executeUpdate('UPDATE s_order_number SET number = number + ? WHERE name = ?', [$incrementNumber, $name]);

                $number += $incrementNumber;

                $this->connection->commit();
            } catch (\Exception $e) {
                $this->connection->rollBack();
                throw $e;
            }

            return $number;
        }

        return $this->decoratedNumberRangeIncrementer->increment($name);
    }
}
