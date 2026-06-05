<?php

if (! function_exists('validate_date')) {
    function validate_date(string $date, string $format = 'Y-m-d')
    {
        if ($format == 'Y-m') {
            $date .= '-01';
            $format = 'Y-m-d';
        }
        $d = DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }
}

if (! function_exists('page_generate')) {
    function page_generate(int $total, int $pagenum, int $limit): array
    {
        $totalPage = (int) ceil($total / $limit);
        $start = ($pagenum - 1) * $limit;
        $start++;
        $end = $pagenum * $limit;
        if ($end > $total) {
            $end = $total;
        }

        if ($total == 0) {
            $start = $total;
        }

        $prev = $pagenum - 1;
        if ($prev < 1) {
            $prev = 0;
        }

        $next = $pagenum + 1;
        if ($next > $totalPage) {
            $next = 0;
        }

        $from = 1;
        $to = $totalPage;

        $toPage = $pagenum - 2;
        if ($toPage > 0) {
            $from = $toPage;
        }

        if ($totalPage >= 5) {
            if ($totalPage > 0) {
                $to = 5 + $toPage;
                if ($to > $totalPage) {
                    $to = $totalPage;
                }
            } else {
                $to = 5;
            }
        }

        $firstpageBool = false;
        $lastpageBool = false;
        $detail = [];
        if ($totalPage <= 1) {
            $detail = [];
        } else {
            for ($i = $from; $i <= $to; $i++) {
                $detail[] = $i;
            }
            if ($from != 1) {
                $firstpageBool = true;
            }
            if ($to != $totalPage) {
                $lastpageBool = true;
            }
        }

        $totalDisplay = 0;
        if ($pagenum < $totalPage) {
            $totalDisplay = $limit;
        }
        if ($pagenum == $totalPage) {
            $totalDisplay = $total % $limit != 0 ? $total % $limit : $limit;
        }

        return [
            'total_data' => $total,
            'total_page' => $totalPage,
            'total_display' => $totalDisplay,
            'first_page' => $firstpageBool,
            'last_page' => $lastpageBool,
            'prev' => $prev,
            'current' => $pagenum,
            'next' => $next,
            'detail' => $detail,
            'start' => $start,
            'end' => $end,
        ];
    }
}

if (! function_exists('sanitization_response')) {
    function sanitization_response(array $data, array $exclude_columns = []): array
    {
        foreach ($data as $key => &$val) {
            if ($val === null) {
                $val = '';
                continue;
            }

            switch (true) {
                case str_ends_with($key, 'object'):
                case str_ends_with($key, 'array'):
                    $decoded = json_decode(empty($val) ? '[]' : $val, true);
                    $val = $decoded === null ? [] : $decoded;
                    break;

                case str_ends_with($key, '_at'):
                case str_ends_with($key, 'datetime'):
                case str_ends_with($key, 'date'):
                    if (empty($val) || str_starts_with($val, '0000-00-00')) {
                        $val = '';
                        break;
                    }

                    $timestamp = strtotime($val);
                    if ($timestamp === false || $timestamp <= 0) {
                        $val = '';
                    } else {
                        $format = (str_ends_with($key, 'datetime') || str_ends_with($key, '_at')) ? 'Y-m-d H:i:s' : 'Y-m-d';
                        $val = date($format, $timestamp);
                    }
                    break;

                case str_ends_with($key, 'bool'):
                    $val = (bool) $val;
                    break;

                case is_numeric($val):
                    if (
                        ($key === 'id' || str_ends_with($key, '_id'))
                        || str_contains($key, 'is_')
                        || str_ends_with($key, 'phone')
                        || in_array($key, $exclude_columns, true)
                    ) {
                        break;
                    }

                    if (str_contains((string) $val, '.')) {
                        $val = (float) $val;
                    } else {
                        $val = (int) $val;
                    }
                    break;
            }
        }

        return $data;
    }
}

if (! function_exists('generate_field_query')) {
    function generate_field_query(array $arr): array
    {
        $array = [
            'sql' => [],
            'field' => [],
        ];

        foreach ($arr as $key => $val) {
            if (! is_numeric($key)) {
                $array['sql'][] = $key . ' as \'' . $val . '\'';
                $array['field'][$val] = $key;
            } else {
                $array['sql'][] = $val;
                $array['field'][$val] = $val;
            }
        }

        return $array;
    }
}

if (! function_exists('filter_params')) {
    function filter_params(array $params, array $fieldAllowed, array $queryReturn = ['query' => '', 'value' => []]): array
    {
        unset($params['sort'], $params['page'], $params['limit'], $params['search'], $params['filter'], $params['pagination_bool']);

        $queryFilter = '';

        foreach ($params as $field => $value) {
            if (isset($fieldAllowed[$field])) {
                $field = $fieldAllowed[$field];
                $fieldKey = $field;
                if (is_array($value)) {
                    foreach ($value as $comparison => $val) {
                        if (str_ends_with($field, 'datetime') || str_ends_with($field, '_at')) {
                            if (validate_date($val)) {
                                $field = "DATE({$field})";
                            } elseif (! validate_date($val, 'Y-m-d H:i:s')) {
                                $val = '';
                            }

                            if ($comparison == 'le' || $comparison == 'ls' || $comparison == 'lse') {
                                $comparison = '';
                            }
                        }
                        if (str_ends_with($field, 'date')) {
                            if (! validate_date($val)) {
                                $val = '';
                            }
                            if ($comparison == 'le' || $comparison == 'ls' || $comparison == 'lse') {
                                $comparison = '';
                            }
                        }
                        if ($val != '') {
                            switch ($comparison) {
                                case 'eq':
                                default:
                                    $queryFilter .= " AND {$field} = :{$fieldKey} ";
                                    $queryReturn['value'][$fieldKey] = $val;
                                    break;

                                case 'neq':
                                    $queryFilter .= " AND {$field} != :{$fieldKey} ";
                                    $queryReturn['value'][$fieldKey] = $val;
                                    break;

                                case 'lt':
                                    $queryFilter .= " AND {$field} < :{$fieldKey} ";
                                    $queryReturn['value'][$fieldKey] = $val;
                                    break;

                                case 'gt':
                                    $queryFilter .= " AND {$field} > :{$fieldKey} ";
                                    $queryReturn['value'][$fieldKey] = $val;
                                    break;

                                case 'lte':
                                    $queryFilter .= " AND {$field} <= :{$fieldKey} ";
                                    $queryReturn['value'][$fieldKey] = $val;
                                    break;

                                case 'gte':
                                    $queryFilter .= " AND {$field} >= :{$fieldKey} ";
                                    $queryReturn['value'][$fieldKey] = $val;
                                    break;

                                case 'le':
                                    $queryFilter .= " AND {$field} LIKE :{$fieldKey} ";
                                    $queryReturn['value'][$fieldKey] = "{$val}%";
                                    break;

                                case 'ls':
                                    $queryFilter .= " AND {$field} LIKE :{$fieldKey} ";
                                    $queryReturn['value'][$fieldKey] = "%{$val}";
                                    break;

                                case 'lse':
                                    $queryFilter .= " AND {$field} LIKE :{$fieldKey} ";
                                    $queryReturn['value'][$fieldKey] = "%{$val}%";
                                    break;

                                case 'in':
                                    $fi = explode(',', $val);
                                    $inKeys = [];
                                    foreach ($fi as $i => $v) {
                                        $ik = $fieldKey . '_in_' . $i;
                                        $inKeys[] = ':' . $ik;
                                        $queryReturn['value'][$ik] = $v;
                                    }
                                    $queryFilter .= " AND {$field} IN (" . implode(', ', $inKeys) . ") ";
                                    break;

                                case 'nin':
                                    $fi = explode(',', $val);
                                    $inKeys = [];
                                    foreach ($fi as $i => $v) {
                                        $ik = $fieldKey . '_nin_' . $i;
                                        $inKeys[] = ':' . $ik;
                                        $queryReturn['value'][$ik] = $v;
                                    }
                                    $queryFilter .= " AND {$field} NOT IN (" . implode(', ', $inKeys) . ") ";
                                    break;
                            }
                        }
                    }
                } else {
                    if (str_ends_with($field, 'datetime') || str_ends_with($field, '_at')) {
                        $field = 'date(' . $field . ')';
                        if (! validate_date($value)) {
                            $value = '';
                        }
                    }
                    if (str_ends_with($field, 'date') && ! validate_date($value)) {
                        $value = '';
                    }
                    if ($value != '') {
                        $queryFilter .= " AND {$field} = :{$fieldKey} ";
                        $queryReturn['value'][$fieldKey] = $value;
                    }
                }
            }
        }
        $queryReturn['query'] .= $queryFilter;

        return $queryReturn;
    }
}

if (! function_exists('filter_params_array')) {
    function filter_params_array(array $whereFilter = [], array $fieldAllowed = [], array $queryReturn = ['query' => '', 'value' => []]): array
    {
        $sqlSearch = '';

        if ($whereFilter != null) {
            foreach ($whereFilter as $row) {
                $type = $row['type'] ?? '';
                $field = $row['field'] ?? '';
                $value = $row['value'] ?? '';
                $comparison = $row['comparison'] ?? '';

                $field = ! isset($fieldAllowed[$field]) ? '' : $fieldAllowed[$field];

                if ($field == '' || $value == '') {
                    $type = '';
                }
                $fieldKey = $field;

                switch ($type) {
                    case 'string':
                        $arrAllowed = ['=', '<', '>', '<>', '!='];
                        if (! in_array($comparison, $arrAllowed)) {
                            $comparison = '=';
                        }

                        switch ($comparison) {
                            case '=':
                                $sqlSearch .= " AND {$field} = :{$fieldKey}";
                                $queryReturn['value'][$fieldKey] = (string) $value;
                                break;

                            case '!=':
                                $sqlSearch .= " AND {$field} != :{$fieldKey}";
                                $queryReturn['value'][$fieldKey] = (string) $value;
                                break;

                            case '<':
                                $sqlSearch .= " AND {$field} LIKE :{$fieldKey}";
                                $queryReturn['value'][$fieldKey] = "{$value}%";
                                break;

                            case '>':
                                $queryReturn['value'][$fieldKey] = "%{$value}";
                                $sqlSearch .= ' AND ' . $field . " LIKE :{$fieldKey}";
                                break;

                            case '<>':
                                $sqlSearch .= " AND {$field} LIKE :{$fieldKey}";
                                $queryReturn['value'][$fieldKey] = "%{$value}%";
                                break;
                        }
                        break;

                    case 'numeric':
                        if (is_numeric($value)) {
                            $arrAllowed = ['=', '<', '>', '<=', '>=', '<>'];
                            if (! in_array($comparison, $arrAllowed)) {
                                $comparison = '=';
                            }
                            $sqlSearch .= " AND {$field} {$comparison} :{$fieldKey}";
                            $queryReturn['value'][$fieldKey] = (float) $value;
                        }
                        break;

                    case 'boolean':
                        $value = $value == 'true' ? '1' : '0';
                        $sqlSearch .= " AND {$field} = :{$fieldKey}";
                        $queryReturn['value'][$fieldKey] = (bool) $value;
                        break;

                    case 'list':
                        if (strstr($value, '::')) {
                            $arrAllowed = ['yes', 'no', 'bet'];
                            if (! in_array($comparison, $arrAllowed)) {
                                $comparison = 'yes';
                            }
                            $fi = explode('::', $value);

                            if ($comparison == 'yes') {
                                $inKeys = [];
                                foreach ($fi as $i => $v) {
                                    $ik = $fieldKey . '_in_' . $i;
                                    $inKeys[] = ':' . $ik;
                                    $queryReturn['value'][$ik] = $v;
                                }
                                $sqlSearch .= " AND {$field} IN (" . implode(', ', $inKeys) . ")";
                            }
                            if ($comparison == 'no') {
                                $inKeys = [];
                                foreach ($fi as $i => $v) {
                                    $ik = $fieldKey . '_nin_' . $i;
                                    $inKeys[] = ':' . $ik;
                                    $queryReturn['value'][$ik] = $v;
                                }
                                $sqlSearch .= " AND {$field} NOT IN (" . implode(', ', $inKeys) . ")";
                            }
                            if ($comparison == 'bet') {
                                $fieldKey1 = $fieldKey . '_1';
                                $fieldKey2 = $fieldKey . '_2';
                                $sqlSearch .= " AND {$field} BETWEEN :{$fieldKey1} AND :{$fieldKey2}";
                                $queryReturn['value'][$fieldKey1] = $fi[0];
                                $queryReturn['value'][$fieldKey2] = $fi[1];
                            }
                        } else {
                            $sqlSearch .= " AND {$field} = :{$fieldKey}";
                            $queryReturn['value'][$fieldKey] = (string) $value;
                        }
                        break;

                    case 'date':
                        if (str_ends_with($field, 'date')) {
                            $value1 = '';
                            $value2 = '';
                            if (strstr($value, '::')) {
                                $date_value = explode('::', $value);
                                $value1 = $date_value[0];
                                $value2 = $date_value[1];
                            } else {
                                $value1 = $value;
                            }

                            $arrAllowed = ['=', '<', '>', '<=', '>=', '<>', 'bet'];
                            if (! in_array($comparison, $arrAllowed)) {
                                $comparison = '=';
                            }
                            if ($comparison == 'bet') {
                                if (validate_date($value1) && validate_date($value2)) {
                                    $fieldKey1 = $fieldKey . '_1';
                                    $fieldKey2 = $fieldKey . '_2';
                                    $sqlSearch .= " AND {$field} BETWEEN :{$fieldKey1} AND :{$fieldKey2}";
                                    $queryReturn['value'][$fieldKey1] = (string) $value1;
                                    $queryReturn['value'][$fieldKey2] = $value2;
                                }
                            } elseif (validate_date($value1)) {
                                $sqlSearch .= " AND {$field} {$comparison} :{$fieldKey}";
                                $queryReturn['value'][$fieldKey] = (string) $value;
                            }
                        }
                        if (str_ends_with($field, 'datetime') || str_ends_with($field, '_at')) {
                            $value1 = '';
                            $value2 = '';
                            if (strstr($value, '::')) {
                                $date_value = explode('::', $value);
                                $value1 = $date_value[0];
                                $value2 = $date_value[1];
                            } else {
                                $value1 = $value;
                            }

                            $arrAllowed = ['=', '<', '>', '<=', '>=', '<>', 'bet'];
                            if (! in_array($comparison, $arrAllowed)) {
                                $comparison = '=';
                            }
                            if ($comparison == 'bet') {
                                $fieldKey1 = $fieldKey . '_1';
                                $fieldKey2 = $fieldKey . '_2';
                                if (validate_date($value1, 'Y-m-d H:i:s') && validate_date($value2, 'Y-m-d H:i:s')) {
                                    $sqlSearch .= " AND {$field} BETWEEN :{$fieldKey1} AND :{$fieldKey2}";
                                    $queryReturn['value'][$fieldKey1] = (string) $value1;
                                    $queryReturn['value'][$fieldKey2] = $value2;
                                } elseif (validate_date($value1) && validate_date($value2)) {
                                    $sqlSearch .= " AND DATE({$field}) BETWEEN :{$fieldKey1} AND :{$fieldKey2}";
                                    $queryReturn['value'][$fieldKey1] = (string) $value1;
                                    $queryReturn['value'][$fieldKey2] = $value2;
                                }
                            } elseif (validate_date($value1, 'Y-m-d H:i:s')) {
                                $sqlSearch .= " AND {$field} {$comparison} :{$fieldKey}";
                                $queryReturn['value'][$fieldKey] = (string) $value1;
                            } elseif (validate_date($value1)) {
                                $sqlSearch .= " AND DATE({$field}) {$comparison} :{$fieldKey}";
                                $queryReturn['value'][$fieldKey] = (string) $value1;
                            }
                        }
                        break;
                }
            }
        }

        $queryReturn['query'] .= $sqlSearch;

        return $queryReturn;
    }
}

if (! function_exists('search_query')) {
    function search_query(string $search, array $field, array $queryReturn = ['query' => '', 'value' => []]): array
    {
        $query = '';

        foreach ($field as $row) {
            if ($search === '' || $search === '0') {
                continue;
            }
            if (str_ends_with($row, '_at') || str_ends_with($row, 'datetime') || str_ends_with($row, 'date')) {
                continue;
            }
            $query .= $row . ' LIKE :search OR ';
        }
        if ($query != '') {
            $query = preg_replace('/ OR $/', '', $query); // Rtrim replacement yang lebih aman
            $queryReturn['query'] = $queryReturn['query'] . ' AND (' . $query . ') ';
            $queryReturn['value']['search'] = "%{$search}%";
        }

        return $queryReturn;
    }
}

if (! function_exists('where_detail')) {
    function where_detail(array $arrWhere, array $queryReturn = ['query' => '', 'value' => []]): array
    {
        foreach ($arrWhere as $key => $value) {
            if ($key === 'or') {
                foreach ($value as $y) {
                    $query = '';

                    foreach ($y as $k => $v) {
                        $kKey = str_replace('.', '_', $k);
                        if (is_array($v) && isset($v['c'])) {
                            if ($v['v'] === null) {
                                if ($v['c'] === '=') {
                                    $query .= " OR {$k} IS NULL";
                                } elseif ($v['c'] === '!=' || $v['c'] === '<>') {
                                    $query .= " OR {$k} IS NOT NULL";
                                }
                            } elseif ($v['c'] === 'BETWEEN') {
                                $fieldKey1 = $kKey . '_1';
                                $fieldKey2 = $kKey . '_2';
                                $query .= " OR {$k} BETWEEN :{$fieldKey1} AND :{$fieldKey2} ";
                                $queryReturn['value'][$fieldKey1] = $v['v'];
                                $queryReturn['value'][$fieldKey2] = $v['v2'];
                            } else {
                                $query .= " OR {$k} {$v['c']} :{$kKey}";
                                $queryReturn['value'][$kKey] = $v['v'];
                            }
                        } else {
                            if ($v === null) {
                                $query .= " OR {$k} IS NULL";
                            } else {
                                $query .= " OR {$k} = :{$kKey}";
                                $queryReturn['value'][$kKey] = $v;
                            }
                        }
                    }
                    if ($query !== '') {
                        $queryReturn['query'] .= ' AND ( ' . ltrim($query, 'OR ') . ')';
                    }
                }
            } elseif (is_array($value) && isset($value['c'])) {
                $keyKey = str_replace('.', '_', $key);
                if ($value['v'] === null) {
                    if ($value['c'] === '=') {
                        $queryReturn['query'] .= " AND {$key} IS NULL";
                    } elseif ($value['c'] === '!=' || $value['c'] === '<>') {
                        $queryReturn['query'] .= " AND {$key} IS NOT NULL";
                    }
                } elseif ($value['c'] === 'BETWEEN') {
                    $fieldKey1 = $keyKey . '_1';
                    $fieldKey2 = $keyKey . '_2';
                    $queryReturn['query'] .= " AND {$key} BETWEEN :{$fieldKey1} AND :{$fieldKey2} ";
                    $queryReturn['value'][$fieldKey1] = $value['v'];
                    $queryReturn['value'][$fieldKey2] = $value['v2'];
                } else {
                    $queryReturn['query'] .= " AND {$key} {$value['c']} :{$keyKey}";
                    $queryReturn['value'][$keyKey] = $value['v'];
                }
            } else {
                $keyKey = str_replace('.', '_', $key);
                if ($value === null) {
                    $queryReturn['query'] .= " AND {$key} IS NULL";
                } else {
                    $queryReturn['query'] .= " AND {$key} = :{$keyKey}";
                    $queryReturn['value'][$keyKey] = $value;
                }
            }
        }

        return $queryReturn;
    }
}

if (! function_exists('where_raw')) {
    function where_raw($rawWhere, array $queryReturn = ['query' => '', 'value' => []], array $bindings = []): array
    {
        if (is_array($rawWhere)) {
            foreach ($rawWhere as $where) {
                if (is_string($where) && trim($where) !== '') {
                    $where = trim($where);
                    $where = preg_replace('/^\s*WHERE\s+/i', '', $where);
                    $where = preg_replace('/^\s*AND\s+/i', '', $where);

                    if ($where !== '') {
                        $queryReturn['query'] .= ' AND ' . $where;
                    }
                }
            }
        } elseif (is_string($rawWhere) && trim($rawWhere) !== '') {
            $rawWhere = trim($rawWhere);
            $rawWhere = preg_replace('/^\s*WHERE\s+/i', '', $rawWhere);
            $rawWhere = preg_replace('/^\s*AND\s+/i', '', $rawWhere);

            if ($rawWhere !== '') {
                $queryReturn['query'] .= ' AND ' . $rawWhere;
            }
        }

        if (!empty($bindings)) {
            $queryReturn['value'] = array_merge($queryReturn['value'], $bindings);
        }

        return $queryReturn;
    }
}

if (! function_exists('generate_code')) {
    function generate_code(PDO $pdo, $table, $field, $where = [], $prefix = '', $digit = 5)
    {
        $queryWhere = where_detail($where);

        $sql = "SELECT
            IFNULL(LPAD(MAX(CAST(RIGHT({$field}, {$digit}) AS SIGNED) + 1), {$digit}, '0'), '" . sprintf('%0' . $digit . 'd', 1) . "') AS code
            FROM {$table}
        ";

        if ($queryWhere['query'] != '') {
            $sql .= ' WHERE 1 ' . ltrim($queryWhere['query'], 'AND');
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($queryWhere['value'] ?? []);

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_OBJ);
            return $prefix . $row->code;
        }

        return $prefix . str_repeat('0', $digit - 1) . '1';
    }
}

if (! function_exists('generate_random_code')) {
    function generate_random_code(PDO $pdo, $table, $field, $length = 5)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        $stmt = $pdo->prepare("SELECT {$field} FROM {$table} WHERE {$field} = :code");
        $stmt->execute(['code' => $randomString]);

        if ($stmt->rowCount() > 0) {
            return generate_random_code($pdo, $table, $field, $length);
        }

        return $randomString;
    }
}

if (! function_exists('generate_detail_query')) {
    function generate_detail_query($query, PDO $pdo, $isArray = true, $returnQuery = false)
    {
        $result = [];

        $whereParams = isset($query['where_detail']) && is_array($query['where_detail']) ? $query['where_detail'] : [];
        $whereQuery = where_detail($whereParams);

        $groupBy = isset($query['group_by']) && $query['group_by'] != '' ? 'GROUP BY ' . $query['group_by'] : '';
        $havingParams = isset($query['having']) && is_array($query['having']) ? $query['having'] : [];
        $havingQuery['query'] = '';
        $havingQuery['value'] = $whereQuery['value'];
        $havingQuery = where_detail($havingParams, $havingQuery);

        $sortParams = [];
        if (isset($query['order']) && $query['order'] != '') {
            $sortParams = explode(',', $query['order']);
        }

        $orderQuery = '';

        foreach ($sortParams as $value) {
            $dir = 'ASC';
            if (str_starts_with($value, '-')) {
                $dir = 'DESC';
                $value = str_replace('-', '', $value);
            }
            $orderQuery .= "{$value} {$dir},";
        }

        $fieldShow = generate_field_query($query['field_show']);

        $selectQuery = empty($fieldShow['sql']) ? '*' : implode(', ', $fieldShow['sql']);

        $sqlQuery = "SELECT {$selectQuery} {$query['table_and_join']}";

        if ($whereQuery['query'] != '') {
            $sqlQuery .= ' WHERE' . ltrim(trim($whereQuery['query']), 'AND');
        }

        if ($groupBy != '') {
            $sqlQuery .= ' ' . $groupBy;
            if ($havingQuery['query'] != '') {
                $sqlQuery .= ' HAVING' . ltrim(trim($havingQuery['query']), 'AND');
            }
        }

        if ($orderQuery != '') {
            $sqlQuery .= ' ORDER BY ' . rtrim($orderQuery, ',');
        }

        $sqlQuery .= ' LIMIT 1';

        if ($returnQuery) {
            return $sqlQuery;
        }

        $stmt = $pdo->prepare($sqlQuery);
        $stmt->execute($havingQuery['value'] ?? []);

        $result = [];

        if ($stmt->rowCount() > 0) {
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                $result = sanitization_response($data, $query['exclude_numeric_conversion'] ?? []);
                if (! $isArray) {
                    $result = (object) $result;
                }
            }
        }

        return ['results' => $result];
    }
}

if (! function_exists('normalize_array')) {
    function normalize_array(array $arr): array
    {
        $normalized = [];

        foreach ($arr as $key => $value) {
            if (is_int($key)) {
                $normalized[$value] = $value;
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}

if (! function_exists('generate_data_query')) {
    function generate_data_query(array $params, array $query, PDO $pdo, $isArray = true, $returnQuery = false)
    {
        $limit = isset($params['limit'])
            ? max(1, min(100, (int) $params['limit']))
            : (isset($query['limit']) && $query['limit'] !== '' ? (int) $query['limit'] : 10);

        $page = isset($params['page']) ? max(1, (int) $params['page']) : 1;
        $start = ($page - 1) * $limit;

        $pagination = true;
        if (isset($params['pagination_bool'])) {
            $pagination = filter_var($params['pagination_bool'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($query['pagination'])) {
            $pagination = filter_var($query['pagination'], FILTER_VALIDATE_BOOLEAN);
        }

        $fieldShow = generate_field_query($query['field_show'] ?? []);
        $filterQuery = ['query' => '', 'value' => []];

        $filterQuery = filter_params($params, $fieldShow['field'], $filterQuery);

        if (isset($params['filter'])) {
            $filterQuery = filter_params_array($params['filter'], $fieldShow['field'], $filterQuery);
        }

        if (! empty($params['search'])) {
            $searchFields = [];
            $querySearchFields = normalize_array($query['field_show']);

            if (! empty($params['field_search'])) {
                $fieldsToProcess = explode(',', $params['field_search']);

                foreach ($fieldsToProcess as $field) {
                    $field = trim($field);
                    if (array_key_exists($field, $querySearchFields)) {
                        $searchFields[] = $field;
                    } else {
                        $key = array_search($field, $querySearchFields);
                        if ($key !== false) {
                            $searchFields[] = $key;
                        }
                    }
                }
            } elseif (! empty($query['field_search'])) {
                $searchFields = $query['field_search'];
            } else {
                $searchFields = array_keys($querySearchFields);
            }

            if (! empty($searchFields)) {
                $filterQuery = search_query($params['search'], $searchFields, $filterQuery);
            }
        }

        $whereParams = isset($query['where_detail']) && is_array($query['where_detail']) ? $query['where_detail'] : [];
        $filterQuery = where_detail($whereParams, $filterQuery);

        if (isset($query['where_raw']) && !empty($query['where_raw'])) {
            $whereRawBindings = isset($query['where_raw_bindings']) && is_array($query['where_raw_bindings']) ? $query['where_raw_bindings'] : [];
            $filterQuery = where_raw($query['where_raw'], $filterQuery, $whereRawBindings);
        }

        $groupBy = ! empty($query['group_by']) ? 'GROUP BY ' . $query['group_by'] : '';
        $havingParams = isset($query['having']) && is_array($query['having']) ? $query['having'] : [];

        $havingQuery = ['query' => '', 'value' => $filterQuery['value']];
        $havingQuery = where_detail($havingParams, $havingQuery);

        $orderQuery = '';
        $sortParams = [];

        $byPass = false;
        if (! empty($query['order'])) {
            $sortParams = explode(',', (string) $query['order']);
            $byPass = true;
        } elseif (! empty($params['sort'])) {
            $sortParams = explode(',', (string) $params['sort']);
        }

        foreach ($sortParams as $field) {
            $dir = str_starts_with($field, '-') ? 'DESC' : 'ASC';
            $field = ltrim($field, '-');

            if ($byPass || isset($fieldShow['field'][$field])) {
                $orderQuery .= "{$field} {$dir},";
            }
        }

        if ($orderQuery !== '') {
            $orderQuery = ' ORDER BY ' . rtrim($orderQuery, ',');
        }

        $selectQuery = empty($fieldShow['sql']) ? '*' : implode(', ', $fieldShow['sql']);

        $sqlQuery = "SELECT {$selectQuery} {$query['table_and_join']}";

        if ($filterQuery['query'] !== '') {
            $sqlQuery .= ' WHERE' . ltrim(trim((string) $filterQuery['query']), 'AND');
        }

        if ($groupBy !== '') {
            $sqlQuery .= ' ' . $groupBy;
            if ($havingQuery['query'] !== '') {
                $sqlQuery .= ' HAVING' . ltrim(trim((string) $havingQuery['query']), 'AND');
            }
        }

        $sqlQuery .= $orderQuery;

        if ($pagination) {
            $sqlQuery .= " LIMIT {$start}, {$limit}";
        }

        if ($returnQuery) {
            return $sqlQuery;
        }

        $stmt = $pdo->prepare($sqlQuery);
        $stmt->execute($havingQuery['value'] ?? []);

        $dataReturn = [];
        $results = [];

        if ($stmt->rowCount() > 0) {
            $dataArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($dataArray as $row) {
                $results[] = sanitization_response($row, $query['exclude_numeric_conversion'] ?? []);
            }
        }

        $dataReturn['results'] = $results;

        if ($pagination) {
            $countQuery = buildCountQuery($query, $filterQuery, $groupBy, $havingQuery, $selectQuery);
            $stmtPagination = $pdo->prepare($countQuery);
            $stmtPagination->execute($havingQuery['value'] ?? []);

            $rowPagination = $stmtPagination->fetch(PDO::FETCH_OBJ);
            $total = (int) ($rowPagination->total ?? 0);

            $dataReturn['pagination'] = page_generate($total, $page, $limit);
        }

        return $dataReturn;
    }

    function buildCountQuery(array $query, array $filterQuery, string $groupBy, array $havingQuery, string $selectQuery)
    {
        if ($groupBy === '') {
            $sql = "SELECT count(*) as total {$query['table_and_join']}";
            if ($filterQuery['query'] !== '') {
                $sql .= ' WHERE' . ltrim(trim((string) $filterQuery['query']), 'AND');
            }

            return $sql;
        }

        $sql = "SELECT count(*) as total FROM ( SELECT {$selectQuery} {$query['table_and_join']}";
        if ($filterQuery['query'] !== '') {
            $sql .= ' WHERE' . ltrim(trim((string) $filterQuery['query']), 'AND');
        }
        $sql .= ' ' . $groupBy;
        if ($havingQuery['query'] !== '') {
            $sql .= ' HAVING' . ltrim(trim((string) $havingQuery['query']), 'AND');
        }

        return $sql . ' ) r';
    }
}

if (! function_exists('select_max')) {
    function select_max(PDO $pdo, $table, $field, $where)
    {
        $queryWhere = where_detail($where);
        $sqlQuery = "SELECT IFNULL(MAX({$field}),0) as jumlah FROM {$table} ";
        if ($queryWhere['query'] != '') {
            $sqlQuery .= 'WHERE ' . ltrim(trim($queryWhere['query']), 'AND');
        }

        $stmt = $pdo->prepare($sqlQuery);
        $stmt->execute($queryWhere['value'] ?? []);

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_OBJ)->jumlah;
        }

        return 0;
    }
}

if (! function_exists('select_min')) {
    function select_min(PDO $pdo, $table, $field, $where)
    {
        $queryWhere = where_detail($where);
        $sqlQuery = "SELECT IFNULL(MIN({$field}),0) as jumlah FROM {$table} ";
        if ($queryWhere['query'] != '') {
            $sqlQuery .= 'WHERE ' . ltrim(trim($queryWhere['query']), 'AND');
        }

        $stmt = $pdo->prepare($sqlQuery);
        $stmt->execute($queryWhere['value'] ?? []);

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_OBJ)->jumlah;
        }

        return 0;
    }
}
