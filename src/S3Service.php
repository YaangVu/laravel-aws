<?php

namespace YaangVu\LaravelAws;

use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\Sts\StsClient;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use stdClass;
use YaangVu\Exceptions\BadRequestException;
use YaangVu\Exceptions\BaseException;
use YaangVu\Exceptions\SystemException;

class S3Service
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
        $this->setVersion(env('AWS_VERSION'))
             ->setRegion(env('AWS_DEFAULT_REGION'))
             ->setAccessKeyId(env('AWS_ACCESS_KEY_ID'))
             ->setSecretAccessKey(env('AWS_SECRET_ACCESS_KEY'))
             ->setBucket(env('AWS_BUCKET'))
             ->setDurationExpire(env('AWS_DURATION_EXPIRE'));
    }

    public function setVersion(string $version): static
    {
        $this->AWS_VERSION = $version;

        return $this;
    }

    public function getVersion(): string
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

    public function setRegion(string $region): static
    {
        $this->AWS_DEFAULT_REGION = $region;

        return $this;
    }

    public function getRegion(): string
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

    public function setBucket(string $bucket): static
    {
        $this->AWS_BUCKET = $bucket;

        return $this;
    }

    public function getBucket(): string
    {
        return $this->AWS_BUCKET;
    }

    #[Pure] #[ArrayShape(['version' => "string", 'region' => "string", 'credentials' => "string[]"])]
    public function getSharedConfig(): array
    {
        return [
            'version'     => $this->getVersion(),
            'region'      => $this->getRegion(),
            'credentials' => [
                'key'    => $this->getAccessKeyId(),
                'secret' => $this->getSecretAccessKey()
            ],
        ];
    }

    /**
     * @return StsClient
     */
    public function getStsClient(): StsClient
    {
        try {
            return new StsClient($this->getSharedConfig());
        } catch (AwsException $awsException) {
            throw new SystemException($awsException->getMessage() ?? __('system-500'), $awsException);
        }
    }

    public function getCredentials(): Credentials
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
                'version'     => $this->getVersion(),
                'region'      => $this->getRegion(),
                'credentials' => $this->getcredentials()
            ];

            return new S3Client($config);
        } catch (S3Exception $s3Exception) {
            throw new SystemException($s3Exception->getMessage() ?? __('system-500'), $s3Exception);
        } catch (AwsException $awsException) {
            throw new SystemException($awsException->getMessage() ?? __('system-500'), $awsException);
        }
    }

    /**
     * @return object
     */
    public function getSessionTokenS3(): object
    {
        try {
            $sessionToken = $this->getStsClient()->getSessionToken(['DurationSeconds' => $this->getDurationExpire()]);

            $metadata = $sessionToken->get('@metadata');

            if (isset($metadata['statusCode']) && $metadata['statusCode'] != 200) {
                throw new BaseException($sessionToken, new Exception(), $metadata['statusCode']);
            }

            $dataResponse              = new stdClass();
            $dataResponse->status      = $metadata['statusCode'];
            $dataResponse->credentials = $sessionToken->get('Credentials');
            $dataResponse->package     = [
                'region' => $this->getRegion(),
                'bucket' => $this->getBucket()
            ];

            return $dataResponse;

        } catch (S3Exception $s3Exception) {
            throw new SystemException($s3Exception->getMessage() ?? __('system-500'), $s3Exception);
        } catch (AwsException $awsException) {
            throw new SystemException($awsException->getMessage() ?? __('system-500'), $awsException);
        }
    }

    /**
     * @param string $url
     * @param string $expiration
     *
     * @return string
     */
    public function createPreSigned(string $url, string $expiration = ''): string
    {
        if (empty($url)) {
            throw new BadRequestException([__("required", ['attribute' => 'urlS3'])], new Exception());
        }

        try {
            if (empty($expiration))
                $expiration = self::EXPIRATION;

            $url = ltrim(parse_url($url, PHP_URL_PATH), '/');

            $command = $this->getS3Client()->getCommand('GetObject', [
                'Bucket' => $this->getBucket(),
                'Key'    => $url
            ]);

            $request = $this->getS3Client()->createPresignedRequest($command, $expiration);

            return $request->getUri()->__toString();
        } catch (AwsException | Exception $awsException) {
            throw new SystemException($awsException->getMessage() ?? __('system-500'), $awsException);
        }
    }

    /**
     * @param string $url
     *
     * @return mixed
     */
    public function getObject(string $url): mixed
    {
        if (empty($url)) {
            throw new BadRequestException([__("required", ['attribute' => 'urlS3'])], new Exception());
        }

        try {
            $path   = parse_url($url);
            $result = $this->getS3Client()->getObject(
                [
                    'Bucket' => $this->getBucket(),
                    'Key'    => ltrim($path['path'], '/')
                ]
            );
            $body   = $result->get('Body');

            return $body->read($result['ContentLength']);
        } catch (AwsException | Exception $awsException) {
            throw new SystemException($awsException->getMessage() ?? __('system-500'), $awsException);
        }
    }
}
