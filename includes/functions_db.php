<?php

#################################### START - DB FUNCTIONS ####################################
/*
    $data = array();
    $data['kullanici_adsoyad'] = 'test 1';
    $data['kullanici_kodu'] = 'test kodu 1';
    $data['kullanici_sifresi'] = 'test sifresi 1';

    echo insert_into('kullanicilar',$data);
 */
function insert_into($table, $vars) {
    global $db;

    $insert_keys = "";
    $insert_values = array();

    $insert_query = 'INSERT INTO ' . $table . ' SET ';

    foreach ($vars as $key => $value) {
        $key = strtolower($key);
        $insert_keys .= $key . ' = :' . $key . ', ';
        $insert_values[$key] = $value;
    }

    $insert_query .= rtrim($insert_keys, ', ');

    $result = $db->prepare($insert_query)->execute($insert_values);

    if ($result) {
        return $db->lastInsertId();
    } else {
        return false;
    }

}

/*
 * hızlı bir şekilde tek kayıt seçmek için
 * ÖRNEK : quick_select('personeller', ['personel_id' => 1]);
 */
function quick_select($table, $vars) {
    global $db;

    $select_keys = '';
    $select_values = array();

    ## SET için
    foreach ($vars as $key => $value) {
        $key = strtolower($key);
        $select_keys .= $key . ' = :' . $key . ' AND ';
        $select_values[$key] = $value;
    }

    $select_query = rtrim($select_keys, ' AND ');

    $query = $db->prepare('SELECT * FROM ' . $table . ' WHERE ' . $select_query . ' LIMIT 1');
    $query->execute($vars);
    $result = $query->fetch();

    if ($result) {
        return $result;
    } else {
        return false;
    }
}

/*
 * hızlı bir şekilde çok kayıt seçmek için
 * ÖRNEK : quick_select_all('personeller', ['personel_id' => 1]);
 */
function quick_select_all($table, $vars, $orderby = '') {
    global $db;

    $select_keys = '';
    $select_values = array();

    if (!empty($orderby)) {
        $orderby = ' ORDER BY ' . $orderby;
    }

    ## SET için
    foreach ($vars as $key => $value) {
        $key = strtolower($key);
        $select_keys .= $key . ' = :' . $key . ' AND ';
        $select_values[$key] = $value;
    }

    $select_query = rtrim($select_keys, ' AND ');

    $query = $db->prepare('SELECT * FROM ' . $table . ' WHERE ' . $select_query . $orderby);
    $query->execute($select_values);
    $results = $query->fetchAll();

    if ($results) {
        return $results;
    } else {
        return array();
    }
}

function select_single($strSQL, $vars) {
    global $db;

    $query = $db->prepare($strSQL);
    $query->execute($vars);
    $result = $query->fetch();

    if ($result) {
        return $result;
    } else {
        return false;
    }
}

function select_all($strSQL, $vars) {
    global $db;

    $query = $db->prepare($strSQL);
    $query->execute($vars);
    $results = $query->fetchAll();

    if ($results) {
        return $results;
    } else {
        return [];
    }
}

/*
 *    where_var sadece tek değere için çalışır. birden fazla koşula bağlı update yapılacaksa bunu kullanma.
 *    update etmeye çalığın alanda (örn: adsoyad) ve where sorgusunda (where id=1 AND adsoyad = 'xxx') aynı isimde alan varsa çakışır
 *
 */
function update_data($table, $vars, $where_var) {
    global $db;

    $update_keys = "";
    $update_values = array();

    $update_query = 'UPDATE ' . $table . ' SET ';

    ## SET için
    foreach ($vars as $key => $value) {
        // $key = strtolower($key);
        $update_keys .= $key . ' = :' . $key . ', ';
        $update_values[$key] = $value;
    }

    $update_query .= rtrim($update_keys, ' , ');

    ## WHERE için
    $update_query .= ' WHERE ';
    foreach ($where_var as $key => $value) {
        // $key = strtolower($key);
        $update_query .= $key . ' = :' . $key . ' AND ';
        $update_values[$key] = $value;
    }

    // remove last AND string
    $update_query = substr($update_query, 0, -5);

    $result = $db->prepare($update_query)->execute($update_values);

    if ($result) {
        return 1;
    } else {
        return 0;
    }
}

function delete_data($table, $where_vars) {
    global $db;

    $delete_values = array();
    $where_keys = "";

    $delete_query = 'DELETE FROM ' . $table;

    ## WHERE için
    foreach ($where_vars as $key => $value) {
        $key = strtolower($key);
        $where_keys .= $key . ' = :' . $key . ' AND ';
        $delete_values[$key] = $value;
    }

    $delete_query .= ' WHERE ' . rtrim($where_keys, ' AND ');

    $result = $db->prepare($delete_query)->execute($delete_values);

    if ($result) {
        return 1;
    } else {
        return 0;
    }
}

function soft_delete($table, $where_var, $sn = NULL) {
    global $db;

    $update_keys = "";
    $update_values = array();

    $update_query = 'UPDATE ' . $table . ' SET d = 1 ';

    if ($sn) $update_query .= ', sn = '. $db->quote($sn);
    ## WHERE için
    foreach ($where_var as $key => $value) {
        $key = strtolower($key);
        $update_query .= ' WHERE ' . $key . ' = :' . $key;
        $update_values[$key] = $value;
    }

    $result = $db->prepare($update_query)->execute($update_values);

    if ($result) {
        return 1;
    } else {
        return 0;
    }
}

function static_query($strSQL) {
    global $db;

    if (strpos($strSQL, 'SELECT ') !== false) {
        $result = $db->query($strSQL)->fetchAll();
        return $result;
    }

    if (strpos($strSQL, 'UPDATE ') !== false) {
        $db->query($strSQL);
        $result = $db->errorInfo();
        if ($result[0] == '000') {
            return true;
        } else {
            return false;
        }
    }

    if (strpos($strSQL, 'DELETE ') !== false) {
        $db->query($strSQL);
        $result = $db->errorInfo();
        if ($result[0] == '000') {
            return true;
        } else {
            return false;
        }
        return $result;
    }

    if (strpos($strSQL, 'INSERT INTO ') !== false) {
        $result = $db->query($strSQL);
        return $db->lastInsertId();
    }

    return false;

}

function static_query_safe($strSQL,$strSQLvars) {
    
    global $db;

    $query = $db->prepare($strSQL);
    $query->execute($strSQLvars);
    $results = $query->fetchAll();

    if ($results) {
        return $results;
    } else {
        return array();
    }

}
#################################### END - DB FUNCTIONS ####################################

?>