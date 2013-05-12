<?php namespace com\github\gooh\CVBacklog;
class Client
{
    /**
     * @var Url
     */
    protected $endpoint;

    /**
     * @param Url $endpoint
     */
    public function __construct(Url $endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * @param array $ids
     * @return array
     */
    public function findByIds(array $ids)
    {
        $data = array();
        foreach (array_chunk($ids, 100) as $i => $batch) {
            // be easy with the SE.API
            usleep(100);
            $result = $this->executeRequest($this->formatEndpoint($batch));
            if (false === isset($result->items)) {
                $this->raiseError($result);
            }
            $data = array_merge($data, $result->items);            
        }
        return $data;
    }

    protected function raiseError($result)
    {
        if (!isset($result->error_id)) {
            throw new \Exception('An unknown Error Occurred: ' . var_export($result, true));
        }
        switch ($result->error_id) {
            case '502':
                throw new ThrottleViolation($result->error_message);
            default:
                throw new \Exception("Unknown SE.Api Error: " . var_export($result, true));
        }
    }

    /**
     * @param string $url
     * @return array
     */
    protected function executeRequest($url)
    {
        stream_context_set_option(
            stream_context_get_default(),
            'http',
            'header',
            'Accept-Encoding: gzip, deflate'
        );
        $data = json_decode(file_get_contents($url));
        return $data === null ? array() : $data;
    }

    /**
     * @param array $ids
     * @return string
     */
    protected function formatEndpoint(array $ids) {
        return  'compress.zlib://' . sprintf(
            urldecode($this->endpoint),
            implode(';', $ids)
        );
    }
}
