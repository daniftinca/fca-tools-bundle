<?php

namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @MongoDB\Document
 * @Vich\Uploadable
 */
class Scale
{

    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $name;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $table;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $type;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $subType;

    /**
     * @MongoDB\Collection
     */
    protected $data;

    /**
     * @MongoDB\ReferenceOne(targetDocument="User", inversedBy="scales")
     */
    protected $user;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $csvFileName;

    /**
     * @var File
     *
     * @Vich\UploadableField(mapping="csv_file", fileNameProperty="csvFileName")
     */
    protected $csvFile;

    /**
     * @MongoDB\ReferenceOne(targetDocument="DatabaseConnection", inversedBy="scales")
     */
    protected $databaseConnection;

    /**
     * @MongoDB\ReferenceOne(targetDocument="Context", inversedBy="scale")
     */
    protected $context;

    /**
     * @var string
     */
    protected $baseFilePath = "web/uploads/scale/files/";

    public function __construct()
    {
        $this->data = array();
    }

    /**
     * @return string
     */
    public function getBaseFilePath()
    {
        return $this->baseFilePath;
    }

    /**
     * @param string $baseFilePath
     */
    public function setBaseFilePath($baseFilePath)
    {
        $this->baseFilePath = $baseFilePath;
    }

    /**
     * Get csv file path
     *
     * @return string
     */
    public function getCsvFilePath()
    {
        return $this->baseFilePath . $this->csvFileName;
    }

    /**
     * @return string
     */
    public function getCsvAssetPath()
    {
        return substr($this->baseFilePath, 4) . $this->csvFileName;
    }

    /**
     * Get id
     *
     * @return string $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return self
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get user
     *
     * @return User $user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set subType
     *
     * @param string $subType
     * @return self
     */
    public function setSubType($subType)
    {
        $this->subType = $subType;
        return $this;
    }

    /**
     * Get subType
     *
     * @return string $subType
     */
    public function getSubType()
    {
        return $this->subType;
    }

    /**
     * Set data
     *
     * @param array $data
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get data
     *
     * @return array $data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set databaseConnection
     *
     * @param DatabaseConnection $databaseConnection
     * @return self
     */
    public function setDatabaseConnection(DatabaseConnection $databaseConnection)
    {
        $this->databaseConnection = $databaseConnection;
        return $this;
    }

    /**
     * Get databaseConnection
     *
     * @return DatabaseConnection $databaseConnection
     */
    public function getDatabaseConnection()
    {
        return $this->databaseConnection;
    }

    /**
     * Set context
     *
     * @param Context $context
     * @return self
     */
    public function setContext(Context $context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Get context
     *
     * @return Context $context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set table
     *
     * @param string $table
     * @return self
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Get table
     *
     * @return string $table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Set csvFileName
     *
     * @param string $csvFileName
     * @return self
     */
    public function setCsvFileName($csvFileName)
    {
        $this->csvFileName = $csvFileName;
        return $this;
    }

    /**
     * Get csvFileName
     *
     * @return string $csvFileName
     */
    public function getCsvFileName()
    {
        return $this->csvFileName;
    }
}
