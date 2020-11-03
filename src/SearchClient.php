<?php


namespace Kennisnet\Edurep;


use Exception;
use Throwable;

class SearchClient
{
    /**
     * @var int
     */
    private $retries = 0;

    /**
     * @throws Throwable
     */
    public function executeQuery(string $request, int $maxRetries): string
    {
        try {
            if (!$curl = curl_init($request)) {
                throw new Exception('Failed to setup curl');
            }
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_ENCODING, "gzip,deflate");
            $result = curl_exec($curl);

            if (!is_string($result)) {
                if (curl_errno($curl) == 56) {
                    # Failure with receiving network data, could be a 403
                    if ($this->retries < $maxRetries) {
                        sleep(1);
                        $this->retries++;

                        return $this->executeQuery($request, $maxRetries);
                    } else {
                        throw new Exception(curl_error($curl));
                    }
                } else {
                    throw new Exception(curl_error($curl));
                }
            }

            return $result;

        } catch (throwable  $exception) {
            throw $exception;
        } finally {
            if (isset($curl) && is_resource($curl)) {
                curl_close($curl);
            }
        }
    }
}