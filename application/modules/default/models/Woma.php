<?php

class Model_Woma extends Model_ModelAbstract
{
    public $id;
    public $name;
    public $url;
    public $logo_id;
    public $taxnumber;
    public $salestax_id;

    public $company;
    public $street;
    public $house;
    public $zip;
    public $city;
    public $country;
    public $phone;
    public $fax;
    public $small_business = false;
    public $register_id;
    public $register_court;
    public $office;
    public $representative;
    public $eco_control_board;
    public $eco_control_id;
    public $type = null;
    
    public $bank_account_holder;
    public $bank_account_number;
    public $bank_id;
    public $bank_name;
    public $bank_swift;
    public $bank_iban;

    public $description;
    public $history;
    public $history_picture_id;
    public $philosophy;
    public $procedure;
    public $procedure_picture_id;
    public $additional;

    public $created;

    private $_products = null;
    private $_categories = null;

    private $_logo = null;
    private $_history_picture = null;
    private $_procedure_picture = null;

    private $_shippingCosts = null;

    private $_country;

    private $_shop_ids = array();

    private $_shops = null;

    public function __construct($data = array())
    {
        parent::init($data);
    }

    public static function getDbTable(){
        return new Model_DbTable_Woma();
    }

    public function delete(){
        $this->deleted = true;
        $this->save();
    }

    public static function getAll($limit = null, $offset = null, $search = ''){
        $db = self::getDbTable()->getAdapter();
        $ret = array();
        
        $query = 'SELECT * FROM epelia_womas';
        
        if($search){
            $search = '%' . $search . '%';
            $query .= ' WHERE id = ' . $db->quote(str_replace('%', '', intVal($search))) . ' OR representative ILIKE ' . $db->quote($search) . ' OR company ILIKE ' . $db->quote($search) . ' OR street ILIKE ' . $db->quote($search) . ' OR zip ILIKE ' .$db->quote($search) . ' OR city ILIKE ' . $db->quote($search) . ' OR taxnumber ILIKE ' . $db->quote($search) . ' OR salestax_id ILIKE ' . $db->quote($search);
        }
        if($limit && !$offset){
            $query .= ' LIMIT ' . $db->quote($limit);
        }
        if($limit && $offset){
            $query .= ' LIMIT ' . $db->quote($limit) . ',' . $db->quote($offset);
        }

        $result = $db->fetchAll($query);
        if($result){
            foreach($result as $r){
                $ret[] =  new self($r);
            }
        }
        return $ret;
    }

    public static function findByPlzAndDistance($plz, $country, $distance){
        $ret = array();
        $db = self::getDbTable()->getAdapter();

        $result = $db->fetchAll('SELECT * FROM epelia_womas WHERE zip IN (SELECT dest.plz FROM geodb_locations dest CROSS JOIN geodb_locations src WHERE ACOS(SIN(RADIANS(src.lat)) * SIN(RADIANS(dest.lat)) + COS(RADIANS(src.lat)) * COS(RADIANS(dest.lat)) * COS(RADIANS(src.lon) - RADIANS(dest.lon))) * 6380 < ? AND src.plz = ? AND src.country = ?)', array($distance, $plz, $country));
        if (is_null($result)) {
            return array();
        }
        foreach($result as $r){
            $ret[] = new self($r);
        }
        return $ret;
    }

    public static function getCount(){
        $select = self::getDbTable()->select();
        $select->from(self::getDbTable(), array('count(*) as amount'));
        $rows = self::getDbTable()->fetchAll($select);
        return($rows[0]->amount);       
    }

    public function insertShops(){
        if(!$this->id){ // this should not happen, but in case we dont have an id we need one
            $this->save();
        }
        $query = self::getDbTable()->getAdapter()->query('DELETE FROM epelia_womas_shops WHERE woma_id = ?', array($this->id));
        foreach($this->_shop_ids as $shopID){
            $query = self::getDbTable()->getAdapter()->query('INSERT INTO epelia_womas_shops(woma_id, shop_id) VALUES(?,?)', array($this->id, $shopID));
        }
    }

    public function getShops(){
        if(is_null($this->_shops)){
            $this->_shops = array();
            foreach($this->_shop_ids as $shopID){
                $this->_shops[] = Model_Shop::find($shopID);
            }
        }
        return $this->_shops;
    }

    
}
