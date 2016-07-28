<?php
namespace Ant\Database;

class Statement extends \PDOStatement {
    /**
     * ��������ִ�е�sql���
     *
     * @return string
     */
    public function __toString() {
        return $this->queryString;
    }

    /**
     * �Ӳ�ѯ�����ȡ��һ��
     *
     * @return array
     */
    public function getRow() {
        return $this->fetch();
    }

    /**
     * ����һ�����л�ȡָ���е�����
     *
     * @param integer $col_number   �����
     * @return mixed
     */
    public function getCol($col_number = 0) {
        return $this->fetch(\PDO::FETCH_COLUMN, $col_number);
    }

    /**
     * ��ȡ��ѯ�����ָ���е����н��
     *
     * @param integer $col_number   �����
     * @return array
     */
    public function getCols($col_number = 0) {
        return $this->fetchAll(\PDO::FETCH_COLUMN, $col_number);
    }

    /**
     * �������еĲ�ѯ�����������ָ�����ֶ�����Ϊ���������key
     *
     * @param string $col
     * @return array
     */
    public function getAll($column = null) {
        if (!$column) {
            return $this->fetchAll();
        }

        $rowset = [];
        while ($row = $this->fetch()) {
            $rowset[ $row[$column] ] = $row;
        }
        return $rowset;
    }
}
