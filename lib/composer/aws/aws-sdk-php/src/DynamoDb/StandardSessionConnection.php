<?php
namespace Aws\DynamoDb;

use Aws\DynamoDb\Exception\DynamoDbException;

/**
 * The standard connection performs the read and write operations to DynamoDB.
 */
class StandardSessionConnection implements SessionConnectionInterface
{
    use SessionConnectionConfigTrait;
    
    /** @var DynamoDbClient The DynamoDB client */
    protected $client;
    
    /**
     * @param DynamoDbClient    $client DynamoDB client
     * @param array             $config Session handler config
     */
    public function __construct(DynamoDbClient $client, array $config = [])
    {
        $this->client = $client;
        $this->initConfig($config);
    }

    public function read($id)
    {
        $item = [];
        try {
            // Execute a GetItem command to retrieve the item.
            $result = $this->client->getItem([
                 'TableName'      => $this->getTableName(),
                 'Key'            => $this->formatKey($id),
                 'ConsistentRead' => $this->isConsistentRead(),
             ]);

            // Get the item values
            $result = isset($result['Item']) ? $result['Item'] : [];
            foreach ($result as $key => $value) {
                $item[$key] = current($value);
            }
        } catch (DynamoDbException $e) {
            // Could not retrieve item, so return nothing.
        }

        return $item;
    }

    public function write($id, $data, $isChanged)
    {
        // Prepare the attributes
        $expires = time() + $this->getSessionLifetime();
        $attributes = [
            $this->getSessionLifetimeAttribute() => ['Value' => ['N' => (string) $expires]],
            'lock' => ['Action' => 'DELETE'],
        ];
        if ($isChanged) {
            if ($data != '') {
                $type = $this->getDataAttributeType();
                if ($type == 'binary') {
                    $attributes[$this->getDataAttribute()] = ['Value' => ['B' => $data]];
                } else {
                    $attributes[$this->getDataAttribute()] = ['Value' => ['S' => $data]];
                }

            } else {
                $attributes[$this->getDataAttribute()] = ['Action' => 'DELETE'];
            }
        }

        // Perform the UpdateItem command
        try {
            return (bool) $this->client->updateItem([
                'TableName'        => $this->getTableName(),
                'Key'              => $this->formatKey($id),
                'AttributeUpdates' => $attributes,
            ]);
        } catch (DynamoDbException $e) {
            return $this->triggerError("Error writing session $id: {$e->getMessage()}");
        }
    }

    public function delete($id)
    {
        try {
            return (bool) $this->client->deleteItem([
                'TableName' => $this->getTableName(),
                'Key'       => $this->formatKey($id),
            ]);
        } catch (DynamoDbException $e) {
            return $this->triggerError("Error deleting session $id: {$e->getMessage()}");
        }
    }

    public function deleteExpired()
    {
        // Create a Scan iterator for finding expired session items
        $scan = $this->client->getPaginator('Scan', [
            'TableName' => $this->getTableName(),
            'AttributesToGet' => [$this->getHashKey()],
            'ScanFilter' => [
                $this->getSessionLifetimeAttribute() => [
                    'ComparisonOperator' => 'LT',
                    'AttributeValueList' => [['N' => (string) time()]],
                ],
                'lock' => [
                    'ComparisonOperator' => 'NULL',
                ]
            ],
        ]);

        // Create a WriteRequestBatch for deleting the expired items
        $batch = new WriteRequestBatch($this->client, $this->getBatchConfig());

        // Perform Scan and BatchWriteItem (delete) operations as needed
        foreach ($scan->search('Items') as $item) {
            $batch->delete(
                [$this->getHashKey() => $item[$this->getHashKey()]],
                $this->getTableName()
            );
        }

        // Delete any remaining items that were not auto-flushed
        $batch->flush();
    }

    /**
     * @param string $key
     *
     * @return array
     */
    protected function formatKey($key)
    {
        return [$this->getHashKey() => ['S' => $key]];
    }

    /**
     * @param string $error
     *
     * @return bool
     */
    protected function triggerError($error)
    {
        trigger_error($error, E_USER_WARNING);

        return false;
    }
}
