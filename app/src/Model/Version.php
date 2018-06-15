<?php

namespace App\Model;

class Version implements \JsonSerializable
{
    //2.1
    protected $minor;

    //2.1.1
    protected $patch;

    //"http://github/applicationtargz"
    /**
     * @var string
     */
    protected $targz;

    // [
    //      "quick start"   => "01.quick-start.html",
    //      "faq"           => "02.faq.html"
    // ]
    /**
     * @var array
     */
    protected $docs;

    /**
     * Version constructor.
     *
     * @param        $minor
     * @param        $patch
     * @param string $targz
     * @param array  $docs
     */
    public function __construct($minor, $patch, string $targz, array $docs = [])
    {
        $this->minor = $minor;
        $this->patch = $patch;
        $this->targz = $targz;
        $this->docs = $docs;
    }

    /**
     * @return mixed
     */
    public function getMinor()
    {
        return $this->minor;
    }

    /**
     * @param mixed $minor
     *
     * @return Version
     */
    public function setMinor($minor): Version
    {
        $this->minor = $minor;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPatch()
    {
        return $this->patch;
    }

    /**
     * @param mixed $patch
     *
     * @return Version
     */
    public function setPatch($patch): Version
    {
        $this->patch = $patch;

        return $this;
    }

    /**
     * @return string
     */
    public function getTargz(): string
    {
        return $this->targz;
    }

    /**
     * @param string $targz
     *
     * @return Version
     */
    public function setTargz(string $targz): Version
    {
        $this->targz = $targz;

        return $this;
    }

    /**
     * @return array
     */
    public function getDocs(): array
    {
        return $this->docs;
    }

    /**
     * @param array $docs
     *
     * @return Version
     */
    public function setDocs(array $docs): Version
    {
        $this->docs = $docs;

        return $this;
    }

    /**
     * @param array $docs
     *
     * @return Version
     */
    public function addDoc(...$docs): Version
    {
        foreach ($docs as $doc) {
            foreach ($doc as $nice => $html) {
                $this->docs[$nice] = $html;
            }
        }

        return $this;
    }

    /**
     * @param mixed $doc
     *
     * @return Version
     */
    public function removeDoc($doc): Version
    {
        if (false !== $key = array_search($doc, $this->docs, true)) {
            array_splice($this->docs, $key, 1);
        }

        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'minor' => $this->minor,
            'patch'  => $this->patch,
            'targz' => $this->targz,
            'docs'  => $this->docs
        ];
    }
}
