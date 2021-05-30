<?php

namespace YaangVu\LaravelAws;

interface StorageS3Service
{

    /**
     * Set the default version to use if none is provided
     *
     * @param string $defaultVersion
     *
     * @return static
     */
    public function setDefaultVersion(string $defaultVersion): static;

    /**
     * Get the default version to use if none is provided
     *
     * @return string
     */
    public function getDefaultVersion(): string;


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
     * @param string $defaultRegion
     *
     * @return static
     */
    public function setDefaultRegion(string $defaultRegion): static;

    /**
     * Get the default region to use if none is provided
     *
     * @return string
     */
    public function getDefaultRegion(): string;

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
     * @param string $defaultBucket
     *
     * @return static
     */
    public function setDefaultBucket(string $defaultBucket): static;

    /**
     * Get the default bucket to use if none is provided
     *
     * @return string
     */
    public function getDefaultBucket(): string;


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
    public function createPresigned(string $url, string $expiration): object;

}
