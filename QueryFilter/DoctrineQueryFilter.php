<?php

namespace Admingenerator\GeneratorBundle\QueryFilter;

class DoctrineQueryFilter extends BaseQueryFilter
{
    public function addDefaultFilter($field, $value)
    {
        list($tableAlias, $filteredField) = $this->addTablePathToField($field);

        if (!is_array($value)) {
            $this->query->andWhere(sprintf('%s.%s = :%s', $tableAlias, $filteredField, $tableAlias.'_'.$filteredField));
            $this->query->setParameter($tableAlias.'_'.$filteredField, $value);
        } elseif (count($value) > 0) {
            $this->query->andWhere(sprintf('%s.%s IN (:%s)', $tableAlias, $filteredField, $tableAlias.'_'.$filteredField));
            $this->query->setParameter($tableAlias.'_'.$filteredField , $value);
        }
    }

    public function addBooleanFilter($field, $value)
    {
        if ("" !== $value) {
            list($tableAlias, $filteredField) = $this->addTablePathToField($field);

            $this->query->andWhere(sprintf('%s.%s = :%s', $tableAlias, $filteredField, $tableAlias.'_'.$filteredField));
            $this->query->setParameter($tableAlias.'_'.$filteredField, !!$value);
        }
    }

    public function addStringFilter($field, $value)
    {
        list($tableAlias, $filteredField) = $this->addTablePathToField($field);

        $this->query->andWhere(sprintf('%s.%s LIKE :%s', $tableAlias, $filteredField, $tableAlias.'_'.$filteredField));
        $this->query->setParameter($tableAlias.'_'.$filteredField, '%'.$value.'%');
    }

    /**
     * @param string $field
     * @param string $value
     */
    public function addTextFilter($field, $value)
    {
        $this->addStringFilter($field, $value);
    }

    public function addCollectionFilter($field, $value)
    {
        list($tableAlias, $filteredField) = $this->addTablePathToField($field);

        if (!is_array($value)) {
            $value = array($value->getId());
        }

        $this->query->groupBy('q');
        $this->query->andWhere(sprintf('%s.%s IN (:%s)', $tableAlias, $filteredField, $tableAlias.'_'.$filteredField));
        $this->query->setParameter($tableAlias.'_'.$filteredField, $value);

    }

    public function addDateFilter($field, $value, $format = 'Y-m-d')
    {
        list($tableAlias, $filteredField) = $this->addTablePathToField($field);

        if (is_array($value)) {
            if (array_key_exists('from', $value)) {
                if (false !== $from = $this->formatDate($value['from'], $format)) {
                    $this->query->andWhere(sprintf('%s.%s >= :%s_from', $tableAlias, $filteredField, $tableAlias.'_'.$filteredField));
                    $this->query->setParameter($tableAlias.'_'.$filteredField.'_from' , $from);
                }
            }

            if (array_key_exists('to', $value)) {
                if (false !== $to = $this->formatDate($value['to'], $format)) {
                    $this->query->andWhere(sprintf('%s.%s <= :%s_to',$tableAlias, $filteredField, $tableAlias.'_'.$filteredField));
                    $this->query->setParameter($tableAlias.'_'.$filteredField.'_to' , $to);
                }
            }

        } else {
            if (false !== $date = $this->formatDate($value, $format)) {
                $this->query->andWhere(sprintf('s.%s = :%s', $tableAlias, $filteredField, $tableAlias.'_'.$filteredField));
                $this->query->setParameter($tableAlias.'_'.$filteredField, $date);
            }
        }
    }

    public function addDatetimeFilter($field, $value, $format = 'Y-m-d H:i:s')
    {
        $this->addDateFilter($field, $value, $format);
    }

    public function addNullFilter($field, $value = null)
    {
        list($tableAlias, $filteredField) = $this->addTablePathToField($field);

        $this->query->andWhere(sprintf('%s.%s IS NULL', $tableAlias, $filteredField));
    }

    public function addNotNullFilter($field, $value = null)
    {
        list($tableAlias, $filteredField) = $this->addTablePathToField($field);

        $this->query->andWhere(sprintf('%s.%s IS NOT NULL', $tableAlias, $filteredField));
    }

    protected function addTablePathToField($field)
    {
        if (!strpos($field, '.')) {
            return array('q', $field);
        }

        $fieldParts = explode('.', $field);
        $filteredField = array_pop($fieldParts);
        $parentTableAlias = 'q';

        foreach ($fieldParts as $field) {
            $this->query->join($parentTableAlias . '.' . $field, $field . '_table_filter_join');
            $parentTableAlias = $field . '_table_filter_join';
        }

        return array($parentTableAlias, $filteredField);
    }
}
