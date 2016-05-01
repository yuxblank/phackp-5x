<?php
/**
 * Created by IntelliJ IDEA.
 * User: yuri.blanc
 * Date: 15/04/2016
 * Time: 14:08
 */

namespace yuxblank\phackp\core;


use yuxblank\phackp\utils\ReflectionUtils;

class QueryBuilder
{

    private $query = '';


    public function __construct()
    {
    }

    public function select ( $properties) {
        $this->query .= 'SELECT '
            .implode(', ', $properties);
        return $this;
    }

    public function from( $tables) {
        $this->query .= ' FROM ' . implode(', ', $tables);
        return $this;
    }

    public function where($conditions) {
        $this->query .= ' WHERE '
            . $conditions;
        return $this;
    }

    public function join($parent, $parentJoin, $child,  $childJoin) {
        $this->query .= ' JOIN '
            . $child
            . ' ON ' . $parent .'.'.$parentJoin.'='.$child.'.'.$childJoin;
        return $this;
    }

    public function innerJoin( $parent,  $parentJoin,  $child,   $childJoin) {
        $this->query .= ' INNER JOIN '
            . $child
            . ' ON ' . $parent .'.'.$parentJoin.'='.$child.'.'.$childJoin;
        return $this;
    }
    public function fullJoin( $parent,  $parentJoin,  $child,   $childJoin) {
        $this->query .= ' FULL JOIN '
            . $child
            . ' ON ' . $parent .'.'.$parentJoin.'='.$child.'.'.$childJoin;
        return $this;
    }
    public function leftJoin( $parent,  $parentJoin,  $child,   $childJoin) {
        $this->query .= ' LEFT JOIN '
            . $child
            . ' ON ' . $parent .'.'.$parentJoin.'='.$child.'.'.$childJoin;
        return $this;
    }
    public function rightJoin( $parent,  $parentJoin,  $child,   $childJoin) {
        $this->query .= ' RIGHT JOIN '
            . $child
            . ' ON ' . $parent .'.'.$parentJoin.'='.$child.'.'.$childJoin;
        return $this;
    }

    public function groupBy( $fields) {
        $this->query .= ' GROUP BY '. implode(', ', $fields);
        return $this;
    }

    public function having($function, $subject, $condition) {
        $this->query .= ' HAVING '. $function .' (' . $subject .' ) ' . $condition;
        return $this;

    }

    public function order( $orders) {
        $this->query .= ' ORDER BY ' . implode(', ', $orders);
        return $this;
    }

    public function limit($min, $max) {
        $this->query .= ' LIMIT ' .$min . ',' . $max;
        return $this;
    }

    public function union () {
        $this->query .= ' UNION ';
        return $this;
    }
    public function unionAll () {
        $this->query .= ' UNION ALL ';
        return $this;
    }


    public function insert($table,  $fields) {
        $this->query .= ' INSERT INTO ' . $table
            . ' (' . implode(', ', $fields) . ') VALUES (';

        foreach ($fields as $field) {
            $this->query .= '?,';
        }
        $this->query = rtrim($this->query, ', ');
        $this->query .= ')';
        return $this;
    }

    public function update($table, $fields) {
        $this->query .= ' UPDATE ' . $table .' SET ';
        for($i=0, $max = count($fields); $i<$max; $i++) {
            $this->query .= $fields[$i] . ' = ?, ';
        }
        $this->query = rtrim($this->query, ', ');
        return $this;
    }

    public function delete( $table) {
        $this->query .= ' DELETE  FROM ' . $table;
        return $this;
    }




    public function addSubQueryBuilder(QueryBuilder $queryBuilder) {
        $this->query .= ' = ( ' . $queryBuilder->getQuery() . ' )';
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }





    private function resolveNamespace() {
        return Application::getNameSpace()['MODEL'].get_class($this->object);
    }
    private function resolveTablename($object) {
        return strtolower(ReflectionUtils::stripNamespace(get_class($object)));
    }

    private function resolveTables( $objects) {
        $tables = array();
        foreach ($objects as $object) {
            $tables[] = $this->resolveTablename($object);
        }
        return $tables;

    }

}

/*
 * SELECT tag.id, tag.tag FROM tag
         *   JOIN post_tag ON tag.id= post_tag.tag_id
         *   JOIN post ON post_tag.post_id=post.id;
 */

/*$queryBuilder = new QueryBuilder();
$queryBuilder->select('tag', array('*'))
    ->from(array('tag'))
    ->join('tag', 'id','post_tag','post_id')
    ->where('id=?');


$queryBuilder2 = new QueryBuilder();
$queryBuilder2->select('tag', array('*'))
    ->from(array('tag'))
    ->join('tag', 'id','post_tag','post_id')
    ->where('id=?')
    ->having('COUNT','tag.id','>1')
    ->order(array('tag.id ASC', 'tag.count DESC'))
    ->limit(0,10)
    ->union()
    ->select('x',array('*'));*/
/*$queryBuilder3 = new QueryBuilder();
$queryBuilder3
    ->insert('tabella', array('id','nome'), array(1,'pippo'));*/
/*$queryBuilder->addSubQueryBuilder($queryBuilder3);*/
/*$queryBuilder4 = new QueryBuilder();
$queryBuilder4
    ->update('tabella', array('id','titolo'),array(1,'prova'));

print_r($queryBuilder4);*/
