<?php

declare(strict_types=1);

namespace YaangVu\LaravelAws\Impl;


use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Aws\Sts\StsClient;
use Aws\S3\S3Client;
use Aws\Credentials\Credentials;
use Illuminate\Support\Facades\Log;
use YaangVu\Exceptions\BadRequestException;
use YaangVu\Exceptions\BaseException;
use YaangVu\Exceptions\SystemException;

class S3ClientService implements StorageS3Service
{

    /**
     * AWS version
     *
     * @var string
     */
    private string $AWS_VERSION;

    /**
     * AWS server configurations access key id
     *
     * @var string
     */
    private string $AWS_ACCESS_KEY_ID;

    /**
     * AWS server configurations secret access key
     *
     * @var string
     */
    private string $AWS_SECRET_ACCESS_KEY;

    /**
     * AWS the region
     *
     * @var string
     */
    private string $AWS_DEFAULT_REGION;

    /**
     * AWS The duration, in seconds, that the credentials should remain valid.
     *
     * @var string
     */
    private string $AWS_DURATION_EXPIRE;

    /**
     * AWS Bucket Name
     *
     * @var string
     */
    private string $AWS_BUCKET;

    /**
     * AWS expire time a pre signed URL
     *
     * @var string
     */
    private const EXPIRATION = '+30 minutes';

    public function __construct()
    {
        $this->init();
    }


    public function init(): void
    {
        $this->setDefaultVersion(env('AWS_VERSION'));
        $this->setDefaultRegion(env('AWS_DEFAULT_REGION'));
        $this->setAccessKeyId(env('AWS_ACCESS_KEY_ID'));
        $this->setSecretAccessKey(env('AWS_SECRET_ACCESS_KEY'));
        $this->setDefaultBucket(env('AWS_BUCKET'));
        $this->setDurationExpire(env('AWS_DURATION_EXPIRE'));
    }

    public function setDefaultVersion(string $defaultVersion): static
    {
        $this->AWS_VERSION = $defaultVersion;

        return $this;
    }

    public function getDefaultVersion(): string
    {
        return $this->AWS_VERSION;
    }

    public function setAccessKeyId(string $accessKeyId): static
    {
        $this->AWS_ACCESS_KEY_ID = $accessKeyId;

        return $this;
    }

    public function getAccessKeyId(): string
    {
        return $this->AWS_ACCESS_KEY_ID;
    }

    public function setSecretAccessKey(string $secretAccessKey): static
    {
        $this->AWS_SECRET_ACCESS_KEY = $secretAccessKey;

        return $this;
    }

    public function getSecretAccessKey(): string
    {
        return $this->AWS_SECRET_ACCESS_KEY;
    }

    public function setDefaultRegion(string $defaultRegion): static
    {
        $this->AWS_DEFAULT_REGION = $defaultRegion;

        return $this;
    }

    public function getDefaultRegion(): string
    {
        return $this->AWS_DEFAULT_REGION;
    }

    public function setDurationExpire(string $durationExpire): static
    {
        $this->AWS_DURATION_EXPIRE = $durationExpire;

        return $this;
    }

    public function getDurationExpire(): string
    {
        return $this->AWS_DURATION_EXPIRE;
    }

    public function setDefaultBucket(string $defaultBucket): static
    {
        $this->AWS_BUCKET = $defaultBucket;

        return $this;
    }

    public function getDefaultBucket(): string
    {
        return $this->AWS_BUCKET;
    }

    public function sharedConfig(): array
    {
        return [
            'version'     => $this->getDefaultVersion(),
            'region'      => $this->getDefaultRegion(),
            'credentials' => [
                'key'    => $this->getAccessKeyId(),
                'secret' => $this->getSecretAccessKey()
            ],
        ];
    }

    public function getStsClient(): StsClient
    {
        try {
            return new StsClient($this->sharedConfig());
        } catch (AwsException $awsException) {
            throw new SystemException($awsException->getMessage() ?? __('system-500'), $awsException);
        }
    }

    public function getcredentials(): Credentials
    {
        try {
            return new Credentials($this->getAccessKeyId(), $this->getSecretAccessKey());
        } catch (AwsException $awsException) {
            throw new SystemException($awsException->getMessage() ?? __('system-500'), $awsException);
        }
    }

    public function getS3Client(): S3Client
    {
        try {

            $config = [
                'version'     => $this->getDefaultVersion(),
                'region'      => $this->getDefaultRegion(),
                'credentials' => $this->getcredentials()
            ];

            return new \Aws\S3\S3Client($config);
        } catch (S3Exception $s3Exception) {
            throw new SystemException($s3Exception->getMessage() ?? __('system-500'), $s3Exception);
        } catch (AwsException $awsException) {
            throw new SystemException($awsException->getMessage() ?? __('system-500'), $awsException);
        }
    }

    public function getSessionTokenS3(): object
    {
        try {
            Log::info("Call API Start: '" . microtime(true) . "'", $this->sharedConfig());
            $sessionToken = $this->getStsClient()->getSessionToken(['DurationSeconds' => $this->getDurationExpire()]);
            Log::info("Call API End: '" . microtime(true) . "'", [$sessionToken]);

            $metadata = $sessionToken->get('@metadata');

            if (isset($metadata['statusCode']) && $metadata['statusCode'] != 200) {
                throw new BaseException($sessionToken, new \Exception(), $metadata['statusCode']);
            }

            $dataResponse              = new \stdClass();
            $dataResponse->status      = $metadata['statusCode'];
            $dataResponse->credentials = $sessionToken->get('Credentials');
            $dataResponse->package     = [
                'region' => $this->getDefaultRegion(),
                'bucket' => $this->getDefaultBucket()
            ];

            return $dataResponse;

        } catch (S3Exception $s3Exception) {
            throw new SystemException($s3Exception->getMessage() ?? __('system-500'), $s3Exception);
        } catch (AwsException $awsException) {
            throw new SystemException($awsException->getMessage() ?? __('system-500'), $awsException);
        }
    }

    public function createPresigned(string $url, string $expiration): object
    {
        if (empty($url)) {
            throw new BadRequestException([__("required", ['attribute' => 'urlS3'])], new \Exception());
        }

        try {
            if (empty($expiration))
                $expiration = self::EXPIRATION;

            $url = ltrim(parse_url($url, PHP_URL_PATH), '/');

            $command = $this->getS3Client()->getCommand('GetObject', [
                'Bucket' => $this->getDefaultBucket(),
                'Key'    => $url
            ]);

            $request                    = $this->getS3Client()->createPresignedRequest($command, $expiration);
            $dataResponse               = new \stdClass();
            $dataResponse->presignedUrl = $request->getUri()->__toString();

            return $dataResponse;

        } catch (AwsException $awsException) {
            throw new SystemException($awsException->getMessage() ?? __('system-500'), $awsException);
        }
    }
}
