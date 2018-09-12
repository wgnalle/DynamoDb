<?php
#DynamoStoreXml.php
#by William Nalle
#Updated: 2018-09-11

require 'aws.phar';

date_default_timezone_set('America/New_York');

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\DynamoDbClient;

class DynamoStoreXml {
        private $client;
        private $region = 'us-east-2';
        private $tableName = 'PostResponseData';

        // This will need to change if we want to use a different method of to authenticate other than instance profiles.
        private function __construct() {
                $this->client = DynamoDbClient::factory(array('region' => $this->region));
        }

        public static function factory() {
                return new DynamoStoreXml();
        }

        // Change the region
        public function SetRegion($region) {
                $this->region = $region;
        }
        public function GetRegion() {
                return $this->region;
        }
        // Change the table name
        public function SetTableName($tableName) {
                $this->tableName = $tableName;
        }
        public function GetTableName() {
                return $tableName;
        }

        // Store a single response in the table
        public function StorePostResponseXml($clientName, $postLogId, $postResponse) {
                $item = array(
                        'clientName' => array('S' => $clientName),
                        'postLogId' => array('N' => $postLogId),
                        'postResponse' => array('S' => $postResponse)
                );

                $param = array(
                        'TableName' => $this->tableName,
						'Item' => $item
                );

                //print_r($param);

                try {
                        $this->client->putItem($param);
                        return 0;
                } catch (DynamoDbException $e) {
                        return $e->getMessage();
                }
        }


        // Return the stored xml response based on client name and post log id
        public function GetPostResponseXml($clientName, $postLogId) {
                $result = $this->client->getItem(array(
                        'ConsistentRead' => true,
                        'TableName' => $this->tableName,
                        'Key' => array('clientName' => array('S' => $clientName),
                                        'postLogId' => array('N' => $postLogId))
                ));

                if (array_key_exists('Item', $result)) {
                        if (array_key_exists('postResponse', $result['Item'])) {
                                return $result['Item']['postResponse']['S'];
                        } else {
                                return 0;
                        }
                }
        }

        // Query the PostResponseData table to find a response for a given client containing a specific node in the response
        public function QueryPostResponseXml($clientName, $varText) {
                $iterator = $this->client->getIterator('Scan', array(
                        'TableName' => 'PostResponseData',
                        'ScanFilter' => array(
                                'clientName' => array(
                                        'AttributeValueList' => array(array('S' => $clientName)),
                                        'ComparisonOperator' => 'EQ'),
                                'postResponse' => array(
                                        'AttributeValueList' => array(array('S' => $varText)),
                                        'ComparisonOperator' => 'CONTAINS')
                )));

                return $iterator;
        }
}

?>

