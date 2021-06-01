<?php

namespace YaangVu\LaravelAws;

interface StorageS3Service
{

    /**
     * Set the default version to use if none is provided
     *
     * @param string $version
     *
     * @return static
     */
    public function setVersion(string $version): static;

    /**
     * Get the default version to use if none is provided
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Set the default Access Key Id to use if none is provided
     *
     * @param string $accessKeyId
     *
     * @return static
     */
    public function setAccessKeyId(string $accessKeyId): static;

    /**
     * Get the default Access Key Id to use if none is provided
     *
     * @return string
     */
    public function getAccessKeyId(): string;


    /**
     * Set the default Secret Access Key to use if none is provided
     *
     * @param string $secretAccessKey
     *
     * @return static
     */
    public function setSecretAccessKey(string $secretAccessKey): static;

    /**
     * Get the default Secret Access Key to use if none is provided
     *
     * @return string
     */
    public function getSecretAccessKey(): string;

    /**
     * Set the default region to use if none is provided
     *
     * @param string $region
     *
     * @return static
     */
    public function setRegion(string $region): static;

    /**
     * Get the default region to use if none is provided
     *
     * @return string
     */
    public function getRegion(): string;

    /**
     * Set the default duration expire to use if none is provided
     *
     * @param string $durationExpire
     *
     * @return static
     */
    public function setDurationExpire(string $durationExpire): static;

    /**
     * Get the default duration expire to use if none is provided
     *
     * @return string
     */
    public function getDurationExpire(): string;


    /**
     * Set the default bucket to use if none is provided
     *
     * @param string $bucket
     *
     * @return static
     */
    public function setBucket(string $bucket): static;

    /**
     * Get the default bucket to use if none is provided
     *
     * @return string
     */
    public function getBucket(): string;

    /**
     * AWS Init Config Connect
     *
     * @return void
     */
    public function init(): void;

    /**
     * AWS Get Session Auth Token
     *
     * @return object
     */
    public function getSessionTokenS3(): object;

    /**
     * Create a pre-signed URL for the given S3 command object.
     *
     * @param string $url
     * @param string $expiration
     *
     * @return object
     */
    public function createPreSigned(string $url, string $expiration): string;

}
