<?php
namespace PunktDe\Testing\Forked\DbUnit\Database\Metadata;

/*
 *  (c) 2020 punkt.de GmbH - Karlsruhe, Germany - https://punkt.de
 *  All rights reserved.
 *
 *  based on DbUnit by Sebastian Bergmann
 */

use PDO;
use PDOException;

/**
 * Provides functionality to retrieve meta data from a Microsoft SQL Server database.
 */
class SqlSrv extends AbstractMetadata
{
    /**
     * No character used to quote schema objects.
     *
     * @var string
     */
    protected $schemaObjectQuoteChar = '';

    /**
     * The command used to perform a TRUNCATE operation.
     *
     * @var string
     */
    protected $truncateCommand = 'TRUNCATE TABLE';

    /**
     * Returns an array containing the names of all the tables in the database.
     *
     * @return array
     */
    public function getTableNames()
    {
        $query = "SELECT name
                    FROM sysobjects
                   WHERE type='U'";

        $statement = $this->pdo->prepare($query);
        $statement->execute();

        $tableNames = [];
        while (($tableName = $statement->fetchColumn(0))) {
            $tableNames[] = $tableName;
        }

        return $tableNames;
    }

    /**
     * Returns an array containing the names of all the columns in the
     * $tableName table.
     *
     * @param string $tableName
     *
     * @return array
     */
    public function getTableColumns($tableName)
    {
        $query = "SELECT c.name
                    FROM syscolumns c
               LEFT JOIN sysobjects o ON c.id = o.id
                   WHERE o.name = '$tableName'";

        $statement = $this->pdo->prepare($query);
        $statement->execute();

        $columnNames = [];
        while (($columnName = $statement->fetchColumn(0))) {
            $columnNames[] = $columnName;
        }

        return $columnNames;
    }

    /**
     * Returns an array containing the names of all the primary key columns in
     * the $tableName table.
     *
     * @param string $tableName
     *
     * @return array
     */
    public function getTablePrimaryKeys($tableName)
    {
        $query     = "EXEC sp_statistics '$tableName'";
        $statement = $this->pdo->prepare($query);
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        $columnNames = [];
        while (($column = $statement->fetch())) {
            if ($column['TYPE'] == 1) {
                $columnNames[] = $column['COLUMN_NAME'];
            }
        }

        return $columnNames;
    }

    /**
     * Allow overwriting identities for the given table.
     *
     * @param string $tableName
     */
    public function disablePrimaryKeys($tableName)
    {
        try {
            $query = "SET IDENTITY_INSERT $tableName ON";
            $this->pdo->exec($query);
        } catch (PDOException $e) {
            // ignore the error here - can happen if primary key is not an identity
        }
    }

    /**
     * Reenable auto creation of identities for the given table.
     *
     * @param string $tableName
     */
    public function enablePrimaryKeys($tableName)
    {
        try {
            $query = "SET IDENTITY_INSERT $tableName OFF";
            $this->pdo->exec($query);
        } catch (PDOException $e) {
            // ignore the error here - can happen if primary key is not an identity
        }
    }
}
