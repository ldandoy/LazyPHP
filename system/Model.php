<?php
/**
 * File system\Model.php
 *
 * @category System
 * @package  Netoverconsulting
 * @author   Loïc Dandoy <ldandoy@overconsulting.net>
 * @license  GNU
 * @link     http://overconsulting.net
 */

namespace system;

use system\Config;
use system\Query;
use system\Db;

/**
 * Class gérant les Models du site
 *
 * @category System
 * @package  Netoverconsulting
 * @author   Loïc Dandoy <ldandoy@overconsulting.net>
 * @license  GNU
 * @link     http://overconsulting.net
 */
class Model
{
    public $model = array();

    /**
     * Constructeur
     *
     * Cette fonction appel la fonction setData au l'initialisation
     * de l'objet
     *
     * @param array $datas Contient les données à ajouter à l'objet
     *
     * @return void
     */
    public function __construct($datas = array())
    {
        if (!empty($datas)) {
            $this->setData($datas);
        } else {
            foreach ($this->permittedColumns as $k => $v) {
                $this->$v = '';
            }
        }
    }

    /**
     * Ajout les données dans l'objet
     *
     * Cette fonction est appelé à l'instanciation de la classe pour
     * charger les données dans l'objet
     *
     * @param array $datas Contient les données à ajouter àl'objet
     *
     * @return void
     */
    public function setData($datas = array())
    {
        $this->id = $datas->id;
        if (isset($this->permittedColumns) && !empty($this->permittedColumns)) {
            foreach ($this->permittedColumns as $k => $v) {
                $this->$v = $datas->$v;
            }
        } else {
            
        }
        $this->created_at = $datas->created_at;
        $this->updated_at = $datas->updated_at;
    }

    /**
     * Create the object in database
     *
     * @param mixed $data 
     *
     * @return bool
     */
    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = $data['created_at'];

        $permittedData = $this->getPermittedData($data);

        $query = new Query();
        $query->insert(array(
            'table' => $this->getTable(),
            'columns' => array_keys($permittedData)
        ));

        return $query->execute($permittedData);
    }

    /**
     * Update the object in database
     *
     * @param mixed $data
     *
     * @return bool
     */
    public function update($data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $permittedData = $this->getPermittedData($data);

        $query = new Query();
        $query->update(array(
            'table' => $this->getTable(),
            'columns' => array_keys($permittedData)
        ));
        $query->where('id = '.$this->id);

        return $query->execute($permittedData);
    }

    /**
     * Delete the object in database
     *
     * @return bool
     */
    public function delete()
    {
        $query = new Query();
        $query->delete(array('table' => $this->getTable()));
        $query->where('id = :id');
        $query->showSql();
        return $query->execute(array('id' => $this->id));
    }

    /**
     * Renvoie tous les enregistrement d'une table
     *
     * Cette fonction permet de récupérer les enregistrement d'une table
     * et les renvoie sous forme d'un tableau objets
     *
     * @return array $return contient tous les objets trouvé en base
     */
    public static function findAll()
    {
        $return = array();
        $class = get_called_class();
        
        $query = new Query();
        $query->select('*');
        $query->from(self::getTableName());
        $rows = $query->executeAndFetchAll();
        
        foreach ($rows as $row) {
            $return[] = new $class($row);
        }
        return $return;
    }

    /**
     * Renvoie l'enregistrement s'il est trouvé
     *
     * Cette fonction permet de récupérer un enregistrement d'une table
     * en le cherchant par son ID
     *
     * @param integer $id contient l'id cherché dans la DB
     *
     * @return \system\Model $return Contient l'objets trouvé en base
     */
    public static function findById($id = 0)
    {
        $class = get_called_class();

        $query = new Query();
        $query->select('*');
        $query->where('id = :id');
        $query->from(self::getTableName());

        $row = $query->executeAndFetch(array('id' => $id));
        
        $return = new $class($row);
        if (isset($return->parent) && !empty($return->parent)) {
            foreach ($return->parent as $k_parent => $v_parent) {
                $parentClass = 'app\\models\\'.$k_parent;
                $parent = $parentClass::findById($v_parent);
                $return->$k_parent = $parent;
            }
        }

        return $return;
    }

    /**
     * Return the name of the table from the static class calling
     *
     * @return string The name of the table to return
     */
    public static function getTableName()
    {
        $tableName = strtolower(getLastElement(explode('\\', get_called_class())))."s";
        return $tableName;
    }

    /**
     * Return the name of the table from the class calling
     *
     * @return string The name of the table to return
     */
    public function getTable()
    {
        $tableName = strtolower(getLastElement(explode('\\', get_class($this))))."s";
        return $tableName;
    }

    /**
     * Get the permitted columns
     *
     * @return mixed
     */
    public function getPermittedColumns()
    {
        return array_merge(
            $this->permittedColumns,
            array('created_at', 'updated_at')
        );
    }

    /**
     * Get data with only the permitted columns
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function getPermittedData($data)
    {
        $permittedData = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $this->getPermittedColumns())) {
                $permittedData[$k] = $v;
            }
        }
        return $permittedData;
    }
}
