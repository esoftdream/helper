<?php

use CodeIgniter\Database\BaseConnection;

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
    /**
     * Generate page information for pagination.
     *
     * @param int $total Total items
     * @param int $pagenum Current page number
     * @param int $limit Items per page
     * @return array<string, int|bool|array<int>> Page information
     */
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

        // ------------- Prev page
        $prev = $pagenum - 1;
        if ($prev < 1) {
            $prev = 0;
        }
        // ------------------------

        // ------------- Next page
        $next = $pagenum + 1;
        if ($next > $totalPage) {
            $next = 0;
        }
        // ----------------------

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

        // looping kotak pagination
        $firstpageBool = false;
        $lastpageBool = false;
        $detail = [];
        if ($totalPage <= 1) {
            $detail = [];
        } else {
            for ($i = $from; $i <= $to; $i++) {
                $detail[] = (int) $i;
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
            if (($total % $limit) != 0) {
                $totalDisplay = $total % $limit;
            } else {
                $totalDisplay = $limit;
            }
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
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    function sanitization_response(array $data): array
    {
        foreach ($data as $key => $val) {
            if (null === $val) {
                $data[$key] = '';
                $val = '';
            }
            if (str_ends_with($key, 'object')) {
                $data[$key] = json_decode(empty($val) ? '{}' : $val, true);
                if (null === $data[$key]) {
                    $data[$key] = [];
                }
            }
            if (str_ends_with($key, 'array')) {
                $data[$key] = json_decode(empty($val) ? '[]' : $val, true);
                if (null === $data[$key]) {
                    $data[$key] = [];
                }
            }
            if (str_ends_with($key, 'date')) {
                if (str_starts_with($val, '0000-00-00')) {
                    $data[$key] = '';
                } else {
                    $data[$key] = date('Y-m-d', strtotime($val));
                    if (str_starts_with($data[$key], '1970-01-01')) {
                        $data[$key] = '';
                    }
                }
            }
            if (str_ends_with($key, 'datetime')) {
                if (str_starts_with($val, '0000-00-00')) {
                    $data[$key] = '';
                } else {
                    $data[$key] = date('Y-m-d H:i:s', strtotime($val));
                    if (str_starts_with($data[$key], '1970-01-01')) {
                        $data[$key] = '';
                    }
                }
            }
            if (
                str_ends_with($key, 'latitude')
                || str_ends_with($key, 'longitude')
                || str_ends_with($key, 'lat')
                || str_ends_with($key, 'long')
            ) {
                if (is_numeric($val)) {
                    $data[$key] = (float) $val;
                }
            } elseif (
                is_numeric($val)
                && ! str_ends_with($key, 'id')
                && ! strstr($key, 'is')
                && ! str_ends_with($key, 'phone')
            ) {
                $data[$key] = (int) $val;
                $val = (int) $val;
            }
            if (str_ends_with($key, 'bool')) {
                $data[$key] = (bool) $val;
            }
        }

        return $data;
    }
}

if (! function_exists('generate_field_query')) {
    /**
     * @param string[] $arr
     * @return array<string, array<string, string>>
     */
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
    /**
     * @param array<string, mixed> $params
     * @param array<string, string> $fieldAllowed
     * @param array<string, string> $queryReturn
     * @return array<string, string>
     */
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
                        if (str_ends_with($field, 'datetime')) {
                            if (validate_date($val)) {
                                $field = "DATE({$field})";
                            } else {
                                if (! validate_date($val, 'Y-m-d H:i:s')) {
                                    $val = '';
                                }
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
                                    $queryFilter .= " AND {$field} = :{$fieldKey}: ";
                                    $queryReturn['value'][$fieldKey] = $val;
                                    break;

                                case 'neq':
                                    $queryFilter .= " AND {$field} != :{$fieldKey}: ";
                                    $queryReturn['value'][$fieldKey] = $val;
                                    break;

                                case 'lt':
                                    $queryFilter .= " AND {$field} < :{$fieldKey}: ";
                                    $queryReturn['value'][$fieldKey] = $val;
                                    break;

                                case 'gt':
                                    $queryFilter .= " AND {$field} > :{$fieldKey}: ";
                                    $queryReturn['value'][$fieldKey] = $val;
                                    break;

                                case 'lte':
                                    $queryFilter .= " AND {$field} <= :{$fieldKey}: ";
                                    $queryReturn['value'][$fieldKey] = $val;
                                    break;

                                case 'gte':
                                    $queryFilter .= " AND {$field} >= :{$fieldKey}: ";
                                    $queryReturn['value'][$fieldKey] = $val;
                                    break;

                                case 'le':
                                    $queryFilter .= " AND {$field} LIKE :{$fieldKey}: ";
                                    $queryReturn['value'][$fieldKey] = "{$val}%";
                                    break;

                                case 'ls':
                                    $queryFilter .= " AND {$field} LIKE :{$fieldKey}: ";
                                    $queryReturn['value'][$fieldKey] = "%{$val}";
                                    break;

                                case 'lse':
                                    $queryFilter .= " AND {$field} LIKE :{$fieldKey}: ";
                                    $queryReturn['value'][$fieldKey] = "%{$val}%";
                                    break;

                                case 'in':
                                    $fi = explode(',', $val);
                                    $queryFilter .= " AND {$field} IN :{$fieldKey}: ";
                                    $queryReturn['value'][$fieldKey] = $fi;
                                    break;

                                case 'nin':
                                    $fi = explode(',', $val);
                                    $queryFilter .= " AND {$field} NOT IN :{$fieldKey}: ";
                                    $queryReturn['value'][$fieldKey] = $fi;
                                    break;

                                default:
                                    $queryFilter .= " AND {$field} = :{$fieldKey}: ";
                                    $queryReturn['value'][$fieldKey] = $val;
                                    break;
                            }
                        }
                    }
                } else {
                    if (str_ends_with($field, 'datetime')) {
                        $field = 'date(' . $field . ')';
                        if (! validate_date($value)) {
                            $value = '';
                        }
                    }
                    if (str_ends_with($field, 'date')) {
                        if (! validate_date($value)) {
                            $value = '';
                        }
                    }
                    if ($value != '') {
                        $queryFilter .= " AND {$field} = :{$fieldKey}: ";
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
    /**
     * @param array<string, array<string, string|bool|int|float>> $whereFilter
     * @param array<string, string> $fieldAllowed
     * @param array<string, string> $queryReturn
     * @return array<string, string>
     */
    function filter_params_array(array $whereFilter = [], array $fieldAllowed = [], array $queryReturn = ['query' => '', 'value' => []]): array
    {
        $sqlSearch = '';

        if ($whereFilter != null) {
            foreach ($whereFilter as $row) {
                $type = $row['type'] ?? '';
                $field = $row['field'] ?? '';
                $value = $row['value'] ?? '';
                $comparison = $row['comparison'] ?? '';

                if (! isset($fieldAllowed[$field])) {
                    $field = '';
                } else {
                    $field = $fieldAllowed[$field];
                }

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
                                $sqlSearch .= " AND {$field} = :{$fieldKey}:";
                                $queryReturn['value'][$fieldKey] = (string) $value;
                                break;

                            case '!=':
                                $sqlSearch .= " AND {$field} != :{$fieldKey}:";
                                $queryReturn['value'][$fieldKey] = (string) $value;
                                break;

                            case '<':
                                $sqlSearch .= " AND {$field} LIKE :{$fieldKey}:";
                                $queryReturn['value'][$fieldKey] = (string) ("{$value}%");
                                break;

                            case '>':
                                $queryReturn['value'][$fieldKey] = (string) ("%{$value}");
                                $sqlSearch .= ' AND ' . $field . " LIKE '%" . $value . "'";
                                break;

                            case '<>':
                                $sqlSearch .= " AND {$field} LIKE :{$fieldKey}:";
                                $queryReturn['value'][$fieldKey] = (string) ("%{$value}%");
                                break;
                        }
                        break;

                    case 'numeric':
                        if (is_numeric($value)) {
                            $arrAllowed = ['=', '<', '>', '<=', '>=', '<>'];
                            if (! in_array($comparison, $arrAllowed)) {
                                $comparison = '=';
                            }
                            $sqlSearch .= " AND {$field} {$comparison} :{$fieldKey}:";
                            $queryReturn['value'][$fieldKey] = (float) $value;
                        }
                        break;

                    case 'boolean':
                        $value = $value == 'true' ? '1' : '0';
                        $sqlSearch .= " AND {$field} = :{$fieldKey}:";
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
                                $sqlSearch .= " AND {$field} IN :{$fieldKey}:";
                                $queryReturn['value'][$fieldKey] = array_map(function ($value) {
                                    return (string) $value;
                                }, $fi);
                            }
                            if ($comparison == 'no') {
                                $sqlSearch .= " AND {$field} NOT IN :{$fieldKey}:";
                                $queryReturn['value'][$fieldKey] = array_map(function ($value) {
                                    return (string) $value;
                                }, $fi);
                            }
                            if ($comparison == 'bet') {
                                $fieldKey1 = $fieldKey . '_1';
                                $fieldKey2 = $fieldKey . '_2';
                                $sqlSearch .= " AND {$field} BETWEEN :{$fieldKey1}: AND :{$fieldKey2}:";
                                $queryReturn['value'][$fieldKey1] = (string) $fi[0];
                                $queryReturn['value'][$fieldKey2] = (string) $fi[1];
                            }
                        } else {
                            $sqlSearch .= " AND {$field} = :{$fieldKey}:";
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
                                    $sqlSearch .= " AND {$field} BETWEEN :{$fieldKey1}: AND :{$fieldKey2}:";
                                    $queryReturn['value'][$fieldKey1] = (string) $value1;
                                    $queryReturn['value'][$fieldKey2] = (string) $value2;
                                }
                            } else {
                                if (validate_date($value1)) {
                                    $sqlSearch .= " AND {$field} {$comparison} :{$fieldKey}:";
                                    $queryReturn['value'][$fieldKey] = (string) $value;
                                }
                            }
                        }
                        if (str_ends_with($field, 'datetime')) {
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
                                    $sqlSearch .= " AND {$field} BETWEEN :{$fieldKey1}: AND :{$fieldKey2}:";
                                    $queryReturn['value'][$fieldKey1] = (string) $value1;
                                    $queryReturn['value'][$fieldKey2] = (string) $value2;
                                } elseif (validate_date($value1) && validate_date($value2)) {
                                    $sqlSearch .= " AND DATE({$field}) BETWEEN :{$fieldKey1}: AND :{$fieldKey2}:";
                                    $queryReturn['value'][$fieldKey1] = (string) $value1;
                                    $queryReturn['value'][$fieldKey2] = (string) $value2;
                                }
                            } else {
                                if (validate_date($value1, 'Y-m-d H:i:s')) {
                                    $sqlSearch .= " AND {$field} {$comparison} :{$fieldKey}:";
                                    $queryReturn['value'][$fieldKey] = (string) $value1;
                                } elseif (validate_date($value1)) {
                                    $sqlSearch .= " AND DATE({$field}) {$comparison} :{$fieldKey}:";
                                    $queryReturn['value'][$fieldKey] = (string) $value1;
                                }
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
    /**
     * @param string $search
     * @param string[] $field
     * @param array<string, string> $queryReturn
     * @return array<string, string>
     */
    function search_query(string $search, array $field, array $queryReturn = ['query' => '', 'value' => []]): array
    {
        $query = '';
        foreach ($field as $row) {
            if (! empty($search)) {
                if (! str_ends_with($row, 'datetime') && ! str_ends_with($row, 'date')) {
                    $query .= $row . ' LIKE :search: OR ';
                }
            }
        }
        if ($query != '') {
            $queryReturn['query'] = $queryReturn['query'] . ' AND (' . rtrim($query, 'OR ') . ') ';
            $queryReturn['value']['search'] = "%{$search}%";
        }

        return $queryReturn;
    }
}

if (! function_exists('where_detail')) {
    /**
     * @param array<string, mixed> $arrWhere
     * @param array<string, string> $queryReturn
     * @return array<string, string>
     */
    function where_detail(array $arrWhere, array $queryReturn = ['query' => '', 'value' => []]): array
    {
        foreach ($arrWhere as $key => $value) {
            if ($key === 'or') {
                foreach ($value as $x => $y) {
                    $query = '';

                    foreach ($y as $k => $v) {
                        if (is_array($v) && isset($v['c'])) {
                            if ($v['c'] === 'BETWEEN') {
                                $fieldKey1 = $k . '_1';
                                $fieldKey2 = $k . '_2';
                                $query .= " OR {$k} BETWEEN :{$fieldKey1}: AND :{$fieldKey2}: ";
                                $queryReturn['value'][$fieldKey1] = $v['v'];
                                $queryReturn['value'][$fieldKey2] = $v['v2'];
                            } else {
                                $query .= " OR {$k} {$v['c']} :{$k}:";
                                $queryReturn['value'][$k] = $v['v'];
                            }
                        } else {
                            $query .= " OR {$k} = :{$k}:";
                            $queryReturn['value'][$k] = $v;
                        }
                    }
                    if ($query !== '') {
                        $queryReturn['query'] .= ' AND ( ' . ltrim($query, 'OR ') . ')';
                    }
                }
            } else {
                if (is_array($value) && isset($value['c'])) {
                    if ($value['c'] === 'BETWEEN') {
                        $fieldKey1 = $key . '_1';
                        $fieldKey2 = $key . '_2';
                        $queryReturn['query'] .= " AND {$key} BETWEEN :{$fieldKey1}: AND :{$fieldKey2}: ";
                        $queryReturn['value'][$fieldKey1] = $value['v'];
                        $queryReturn['value'][$fieldKey2] = $value['v2'];
                    } else {
                        $queryReturn['query'] .= " AND {$key} {$value['c']} :{$key}:";
                        $queryReturn['value'][$key] = $value['v'];
                    }
                } else {
                    $queryReturn['query'] .= " AND {$key} = :{$key}:";
                    $queryReturn['value'][$key] = $value;
                }
            }
        }

        return $queryReturn;
    }
}

if (! function_exists('generate_code')) {
    function generate_code(BaseConnection $db, $table, $field, $where = [], $prefix = '', $digit = 5)
    {
        $queryWhere = where_detail($where);

        $sql = "SELECT
            IFNULL(LPAD(MAX(CAST(RIGHT({$field}, {$digit}) AS SIGNED) + 1), {$digit}, '0'), '" . sprintf('%0' . $digit . 'd', 1) . "') AS code
            FROM {$table}
        ";
        if ($queryWhere['query'] != '') {
            $sql .= ' WHERE 1 ' . ltrim($queryWhere['query'], 'AND');
            $query = $db->query($sql, $queryWhere['value']);
        } else {
            $query = $db->query($sql);
        }

        if ($query->getNumRows() > 0) {
            $row = $query->getRow();

            return $prefix . $row->code;
        }

        return $prefix . str_repeat('0', $digit - 1) . '1';
    }
}

if (! function_exists('generate_random_code')) {
    function generate_random_code(BaseConnection $db, $table, $field, $length = 5)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        $data = $db->query("SELECT {$field} FROM {$table} WHERE {$field} = '{$randomString}'");
        if ($data->getNumRows() > 0) {
            return generate_random_code($db, $table, $field, $length);
        }

        return $randomString;
    }
}

if (! function_exists('generate_detail_query')) {
    function generate_detail_query($query, BaseConnection $db, $isArray = true, $returnQuery = false)
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

        $query = $db->query($sqlQuery, $havingQuery['value']);

        $result = [];

        if ($query->getNumRows() > 0) {
            $data = $query->getRowArray();
            $result = sanitization_response($data);
            if (! $isArray) {
                $result = (object) $result;
            }
        }

        if ($returnQuery) {
            return (string) $db->getLastQuery();
        }

        return ['results' => $result];
    }
}

if (! function_exists('generate_data_query')) {
    function generate_data_query(array $params, array $query, BaseConnection $db, $isArray = true, $returnQuery = false)
    {
        // =========================
        // Pagination Setup
        // =========================
        $limit = isset($params['limit'])
            ? max(1, min(100, (int) $params['limit']))
            : (isset($query['limit']) && $query['limit'] !== '' ? (int) $query['limit'] : 10);

        $page = isset($params['page']) ? max(1, (int) $params['page']) : 1;
        $start = ($page - 1) * $limit;

        // normalize pagination bool
        $pagination = true;
        if (isset($params['pagination_bool'])) {
            $pagination = filter_var($params['pagination_bool'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($query['pagination'])) {
            $pagination = filter_var($query['pagination'], FILTER_VALIDATE_BOOLEAN);
        }

        // =========================
        // Fields & Filtering
        // =========================
        $fieldShow = generate_field_query($query['field_show'] ?? []);
        $filterQuery = ['query' => '', 'value' => []];

        $filterQuery = filter_params($params, $fieldShow['field'], $filterQuery);

        if (isset($params['filter'])) {
            $filterQuery = filter_params_array($params['filter'], $fieldShow['field'], $filterQuery);
        }

        if (! empty($params['search'])) {
            $filterQuery = search_query($params['search'], $query['field_search'] ?? [], $filterQuery);
        }

        $whereParams = isset($query['where_detail']) && is_array($query['where_detail']) ? $query['where_detail'] : [];
        $filterQuery = where_detail($whereParams, $filterQuery);

        $groupBy = ! empty($query['group_by']) ? 'GROUP BY ' . $query['group_by'] : '';
        $havingParams = isset($query['having']) && is_array($query['having']) ? $query['having'] : [];

        $havingQuery = ['query' => '', 'value' => $filterQuery['value']];
        $havingQuery = where_detail($havingParams, $havingQuery);

        // =========================
        // Sorting
        // =========================
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

        // =========================
        // Build Select Query
        // =========================
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

        // =========================
        // Execute Main Query
        // =========================
        $queryResult = $db->query($sqlQuery, $havingQuery['value']);

        $dataReturn = [];
        $dataReturn['results'] = [];

        if ($queryResult->getNumRows() > 0) {
            foreach ($queryResult->getResultArray() as $row) {
                $dataReturn['results'][] = ! $isArray
                    ? (object) sanitization_response($row)
                    : sanitization_response($row);
            }
        }

        // Debug mode: return SQL string
        if ($returnQuery) {
            return $sqlQuery;
        }

        // =========================
        // Pagination Count
        // =========================
        if ($pagination) {
            $countQuery = buildCountQuery($query, $filterQuery, $groupBy, $havingQuery, $selectQuery);
            $queryPagination = $db->query($countQuery, $havingQuery['value']);
            $total = (int) $queryPagination->getRow()->total;
            $dataReturn['pagination'] = page_generate($total, $page, $limit);
        }

        return $dataReturn;
    }

    /**
     * Helper untuk membangun query count
     *
     * @param mixed $query
     * @param mixed $filterQuery
     * @param mixed $groupBy
     * @param mixed $havingQuery
     * @param mixed $selectQuery
     */
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
    function select_max(BaseConnection $db, $table, $field, $where)
    {
        $queryWhere = where_detail($where);
        $sqlQuery = "SELECT IFNULL(MAX({$field}),0) as jumlah FROM {$table} ";
        if ($queryWhere['query'] != '') {
            $sqlQuery .= 'WHERE ' . ltrim(trim($queryWhere['query']), 'AND');
        }
        // echo $sqlQuery;
        $queryResult = $db->query($sqlQuery, $queryWhere['value']);
        // echo $db->getLastQuery();
        if ($queryResult->getNumRows() > 0) {
            return $queryResult->getRow()->jumlah;
        }

        return 0;
    }
}
if (! function_exists('select_min')) {
    function select_min(BaseConnection $db, $table, $field, $where)
    {
        $queryWhere = where_detail($where);
        $sqlQuery = "SELECT IFNULL(MIN({$field}),0) as jumlah FROM {$table} ";
        if ($queryWhere['query'] != '') {
            $sqlQuery .= 'WHERE ' . ltrim(trim($queryWhere['query']), 'AND');
        }
        $queryResult = $db->query($sqlQuery, $queryWhere['value']);
        if ($queryResult->getNumRows() > 0) {
            return $queryResult->getRow()->jumlah;
        }

        return 0;
    }
}

if (! function_exists('generate_client_code')) {
    function generate_client_code(BaseConnection $db)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < 5; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        $data = $db->query("SELECT client_code FROM client WHERE client_code = '{$randomString}'");
        if ($data->getNumRows() > 0) {
            return generate_client_code($db);
        }

        return $randomString;
    }
}

if (! function_exists('insert_media')) {
    function insert_media(BaseConnection $db, $caption, $url, $type, $mime, $full_path, $masterId, $table)
    {
        $data = [
            'media_caption' => $caption,
            'media_url' => $url,
            'media_type' => $type,
            'media_mime' => $mime,
            'media_full_path' => $full_path,
            'media_datetime' => date('Y-m-d H:i:s'),
        ];

        $db->table('site_media')->insert($data);

        $mediaId = $db->insertID();

        $db->table('site_media_relation')->insert([
            'media_relation_media_id' => $mediaId,
            'media_relation_master_id' => $masterId,
            'media_relation_table' => $table,
        ]);
    }
}

if (! function_exists('delete_media')) {
    function delete_media(BaseConnection $db, $masterId, $typeMaster)
    {
        $mediaId = $db->table('site_media_relation')
            ->select('media_relation_media_id')
            ->where('media_relation_table', $typeMaster)
            ->where('media_relation_master_id', $masterId)
            ->get()
            ->getResultArray();

        if (! empty($mediaId)) {
            $mediaId = array_column($mediaId, 'media_relation_media_id');

            $files = $db->table('site_media')
                ->select('media_full_path')
                ->whereIn('media_id', $mediaId)
                ->get()
                ->getResultArray();

            $db->table('site_media')->whereIn('media_id', $mediaId)->delete();

            foreach ($files as $file) {
                if (file_exists($file['media_full_path'])) {
                    @unlink($file['media_full_path']);
                }
            }
        }
    }
}
