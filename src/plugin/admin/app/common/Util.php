<?php

namespace plugin\admin\app\common;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use plugin\admin\app\model\Option;
use process\Monitor;
use stdClass;
use support\Db;
use support\exception\BusinessException;
use Throwable;
use Workerman\Timer;
use Workerman\Worker;

class Util
{
    /**
     * 密码哈希
     * @param $password
     * @param string $algo
     * @return string
     */
    public static function passwordHash($password, string $algo = PASSWORD_DEFAULT): string
    {
        return password_hash($password, $algo);
    }

    /**
     * 验证密码哈希
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function passwordVerify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * 获取webman-admin数据库连接
     * @return Connection
     */
    public static function db(): Connection
    {
        return Db::connection('plugin.admin.sqlite');
    }

    /**
     * 数据库版本
     * @param bool $showDriverName 显示驱动
     * @return string
     */
    public static function databaseVersion(bool $showDriverName = false): string
    {
        $db = static::db();
        $version = match ($db->getDriverName()) {
            'mysql' => $db->select('select VERSION() as version')[0]->version ?? 'unknown',
            'sqlite' => $db->select('SELECT sqlite_version() AS version')[0]->version ?? 'unknown',
            default => 'unknown'
        };

        if ($showDriverName && $version && 'unknown' !== $version) {
            return $db->getDriverName() . ' ' . $version;
        }
        return $version;
    }

    /**
     * 获取数据表信息
     * @param string $table
     * @return array
     */
    public static function tableInfo(string $table): array
    {
        $db = static::db();
        return match ($db->getDriverName()) {
            'mysql' => $db->select("desc `$table`"),
            'sqlite' => $db->select("PRAGMA table_info(`$table`)"),
            default => []
        };
    }

    /**
     * 获取数据库表结构数组
     * @param string $table 表名
     * @return array
     * @throws BusinessException
     */
    public static function getTableDescField(string $table): array
    {
        $driverName = Util::db()->getDriverName();
        if (!in_array($driverName, ['sqlite', 'mysql'])) {
            throw new BusinessException("{$driverName}数据库驱动不支持");
        }
        if ($driverName == 'sqlite') {
            $sql = "PRAGMA table_info(`$table`)";
            $column_key = "name";
            $index_key = "name";
        } else {
            $sql = "desc `$table`";
            $column_key = "Field";
            $index_key = "Field";
        }
        $allow_column = Util::db()->select($sql);
        if (!$allow_column) {
            throw new BusinessException('表不存在');
        }
        return array_column($allow_column, $column_key, $index_key);
    }

    /**
     * @param string $table
     * @return array
     * @throws BusinessException
     */
    public static function getTableDescType(string $table): array
    {
        $driverName = Util::db()->getDriverName();
        if (!in_array($driverName, ['sqlite', 'mysql'])) {
            throw new BusinessException("{$driverName}数据库驱动不支持");
        }
        if ($driverName == 'sqlite') {
            $sql = "PRAGMA table_info(`$table`)";
            $column_key = "type";
            $index_key = "name";
        } else {
            $sql = "desc `$table`";
            $column_key = "Type";
            $index_key = "Field";
        }
        $allow_column = Util::db()->select($sql);
        if (!$allow_column) {
            throw new BusinessException('表不存在');
        }
        return array_column($allow_column, $column_key, $index_key);
    }

    /**
     * 获取SchemaBuilder
     * @return Builder
     */
    public static function schema(): Builder
    {
        return Db::schema('plugin.admin.sqlite');
    }

    /**
     * 获取语义化时间
     * @param $time
     * @return string
     */
    public static function humanDate($time): string
    {
        $timestamp = is_numeric($time) ? $time : strtotime($time);
        $dur = time() - $timestamp;
        if ($dur < 0) {
            return date('Y-m-d', $timestamp);
        } else {
            if ($dur < 60) {
                return $dur . '秒前';
            } else {
                if ($dur < 3600) {
                    return floor($dur / 60) . '分钟前';
                } else {
                    if ($dur < 86400) {
                        return floor($dur / 3600) . '小时前';
                    } else {
                        if ($dur < 2592000) { // 30天内
                            return floor($dur / 86400) . '天前';
                        } else {
                            return date('Y-m-d', $timestamp);
                        }
                    }
                }
            }
        }
    }

    /**
     * 格式化文件大小
     * @param $file_size
     * @return string
     */
    public static function formatBytes($file_size): string
    {
        $size = sprintf("%u", $file_size);
        if ($size == 0) {
            return ("0 Bytes");
        }
        $size_name = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
        return round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $size_name[$i];
    }

    /**
     * 数据库字符串转义
     * @param $var
     * @return false|string
     */
    public static function pdoQuote($var)
    {
        return Util::db()->getPdo()->quote($var, \PDO::PARAM_STR);
    }

    /**
     * 检查表名是否合法
     * @param string $table
     * @return string
     * @throws BusinessException
     */
    public static function checkTableName(string $table): string
    {
        if (!preg_match('/^[a-zA-Z_0-9]+$/', $table)) {
            throw new BusinessException('表名不合法');
        }
        return $table;
    }

    /**
     * 变量或数组中的元素只能是字母数字下划线组合
     * @param $var
     * @return mixed
     * @throws BusinessException
     */
    public static function filterAlphaNum($var)
    {
        $vars = (array)$var;
        array_walk_recursive($vars, function ($item) {
            if (is_string($item) && !preg_match('/^[a-zA-Z_0-9]+$/', $item)) {
                throw new BusinessException('参数不合法');
            }
        });
        return $var;
    }

    /**
     * 变量或数组中的元素只能是字母数字
     * @param $var
     * @return mixed
     * @throws BusinessException
     */
    public static function filterNum($var)
    {
        $vars = (array)$var;
        array_walk_recursive($vars, function ($item) {
            if (is_string($item) && !preg_match('/^[0-9]+$/', $item)) {
                throw new BusinessException('参数不合法');
            }
        });
        return $var;
    }

    /**
     * 检测是否是合法URL Path
     * @param $var
     * @return string
     * @throws BusinessException
     */
    public static function filterUrlPath($var): string
    {
        if (!is_string($var) || !preg_match('/^[a-zA-Z0-9_\-\/&?.]+$/', $var)) {
            throw new BusinessException('参数不合法');
        }
        return $var;
    }

    /**
     * 检测是否是合法Path
     * @param $var
     * @return string
     * @throws BusinessException
     */
    public static function filterPath($var): string
    {
        if (!is_string($var) || !preg_match('/^[a-zA-Z0-9_\-\/]+$/', $var)) {
            throw new BusinessException('参数不合法');
        }
        return $var;
    }

    /**
     * 类转换为url path
     * @param $controller_class
     * @return false|string
     */
    static function controllerToUrlPath($controller_class)
    {
        $key = strtolower($controller_class);
        $action = '';
        if (strpos($key, '@')) {
            [$key, $action] = explode('@', $key, 2);
        }
        $prefix = 'plugin';
        $paths = explode('\\', $key);
        if (count($paths) < 2) {
            return false;
        }
        $base = '';
        if (str_starts_with($key, "$prefix\\")) {
            if (count($paths) < 4) {
                return false;
            }
            array_shift($paths);
            $plugin = array_shift($paths);
            $base = "/app/$plugin/";
        }
        array_shift($paths);
        foreach ($paths as $index => $path) {
            if ($path === 'controller') {
                unset($paths[$index]);
            }
        }
        $suffix = 'controller';
        $code = $base . implode('/', $paths);
        if (substr($code, -strlen($suffix)) === $suffix) {
            $code = substr($code, 0, -strlen($suffix));
        }
        return $action ? "$code/$action" : $code;
    }

    /**
     * 转换为驼峰
     * @param string $value
     * @return string
     */
    public static function camel(string $value): string
    {
        static $cache = [];
        $key = $value;

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return $cache[$key] = str_replace(' ', '', $value);
    }

    /**
     * 转换为小驼峰
     * @param $value
     * @return string
     */
    public static function smCamel($value): string
    {
        return lcfirst(static::camel($value));
    }

    /**
     * 获取注释中第一行
     * @param $comment
     * @return false|mixed|string
     */
    public static function getCommentFirstLine($comment)
    {
        if ($comment === false) {
            return false;
        }
        foreach (explode("\n", $comment) as $str) {
            if ($s = trim($str, "*/\ \t\n\r\0\x0B")) {
                return $s;
            }
        }
        return $comment;
    }

    /**
     * 表单类型到插件的映射
     * @return \string[][]
     */
    public static function methodControlMap(): array
    {
        return [
            //method=>[控件]
            'integer' => ['InputNumber'],
            'string' => ['Input'],
            'text' => ['TextArea'],
            'date' => ['DatePicker'],
            'enum' => ['Select'],
            'float' => ['Input'],

            'tinyInteger' => ['InputNumber'],
            'smallInteger' => ['InputNumber'],
            'mediumInteger' => ['InputNumber'],
            'bigInteger' => ['InputNumber'],

            'unsignedInteger' => ['InputNumber'],
            'unsignedTinyInteger' => ['InputNumber'],
            'unsignedSmallInteger' => ['InputNumber'],
            'unsignedMediumInteger' => ['InputNumber'],
            'unsignedBigInteger' => ['InputNumber'],

            'decimal' => ['Input'],
            'double' => ['Input'],

            'mediumText' => ['TextArea'],
            'longText' => ['TextArea'],

            'dateTime' => ['DateTimePicker'],

            'time' => ['DateTimePicker'],
            'timestamp' => ['DateTimePicker'],

            'char' => ['Input'],

            'binary' => ['Input'],

            'json' => ['input']
        ];
    }

    /**
     * 数据库类型到插件的转换
     * @param $type
     * @return string
     */
    public static function typeToControl($type): string
    {
        if (stripos($type, 'int') !== false) {
            return 'inputNumber';
        }
        if (stripos($type, 'time') !== false || stripos($type, 'date') !== false) {
            return 'dateTimePicker';
        }
        if (stripos($type, 'text') !== false) {
            return 'textArea';
        }
        if ($type === 'enum') {
            return 'select';
        }
        return 'input';
    }

    /**
     * 数据库类型到表单类型的转换
     * @param string $type
     * @param bool $unsigned
     * @return string
     */
    public static function typeToMethod(string $type, bool $unsigned = false): string
    {
        $type = static::getTypeBySqlite($type);
        $map = [
            'int' => 'integer',
            'tinyint' => 'integer',
            'varchar' => 'string',
            'text' => 'string',
            'mediumtext' => 'mediumText',
            'longtext' => 'longText',
            'datetime' => 'dateTime',
        ];
        return $map[$type] ?? $type;
    }

    /**
     * 获取数据类型
     * - SQLite数据库
     * @param string $type
     * @return string
     */
    public static function getTypeBySqlite(string $type): string
    {
        return preg_replace('/\(\d+\)/', '', strtolower($type));
    }

    /**
     * 按表获取摘要
     * @param string $table
     * @param string|null $section
     * @return array
     * @throws BusinessException
     */
    public static function getSchema(string $table, string $section = null): array
    {
        Util::checkTableName($table);
        $schema_raw = $section !== 'table' ? Util::tableInfo($table) : [];
        $forms = [];
        $columns = [];
        $primary_key = [];
        foreach ($schema_raw as $item) {
            $field = $item->name;
            if ($item->pk) {
                $primary_key[] = $field;
            }
            $columns[$field] = [
                'field' => $field,
                'type' => Util::typeToMethod($item->type, (bool)strpos($item->type, 'unsigned')),
                'comment' => $item->name,
                'default' => $item->dflt_value,
                'length' => static::getLengthValueBySqlite($item),
                'nullable' => $item->dflt_value !== 'NO',
                'primary_key' => $item->name === 'id',
                'auto_increment' => $item->name === 'id'
            ];

            $forms[$field] = [
                'field' => $field,
                'comment' => $field,
                'control' => static::typeToControl($item->type),
                'form_show' => $field !== 'id',
                'list_show' => true,
                'enable_sort' => false,
                'searchable' => false,
                'search_type' => 'normal',
                'control_args' => '',
            ];
        }
        //$indexes = !$section || in_array($section, ['keys', 'table']) ? Util::db()->select("SHOW INDEX FROM `$table`") : [];
        $keys = [];
        /*foreach ($indexes as $index) {
            $key_name = $index->Key_name;
            if (!isset($keys[$key_name])) {
                $keys[$key_name] = [
                    'name' => $key_name,
                    'columns' => [],
                    'type' => $index->Non_unique == 0 ? 'unique' : 'normal'
                ];
            }
            $keys[$key_name]['columns'][] = $index->Column_name;
        }*/

        $data = [
            'table' => ['name' => $table, 'comment' => $table, 'primary_key' => $primary_key],
            'columns' => $columns,
            'forms' => $forms,
            'keys' => array_reverse($keys, true)
        ];

        $schema = Option::where('name', "table_form_schema_$table")->value('value');
        $form_schema_map = $schema ? json_decode($schema, true) : [];

        foreach ($data['forms'] as $field => $item) {
            if (isset($form_schema_map[$field])) {
                $data['forms'][$field] = $form_schema_map[$field];
            }
        }

        return $section ? $data[$section] : $data;
    }

    /**
     * 获取字段长度或默认值
     * @param $schema
     * @return string
     */
    public static function getLengthValue($schema): string
    {
        $type = $schema->DATA_TYPE;
        if (in_array($type, ['float', 'decimal', 'double'])) {
            return "{$schema->NUMERIC_PRECISION},{$schema->NUMERIC_SCALE}";
        }
        if ($type === 'enum') {
            return implode(',', array_map(function($item){
                return trim($item, "'");
            }, explode(',', substr($schema->COLUMN_TYPE, 5, -1))));
        }
        if (in_array($type, ['varchar', 'text', 'char'])) {
            return $schema->CHARACTER_MAXIMUM_LENGTH;
        }
        if (in_array($type, ['time', 'datetime', 'timestamp'])) {
            return $schema->CHARACTER_MAXIMUM_LENGTH;
        }
        return '';
    }

    /**
     * 获取字段长度
     * @param stdClass $schema
     * @return string
     */
    public static function getLengthValueBySqlite(stdClass $schema): string
    {
        $type = $schema->type;
        if (preg_match('/\((\d+)\)/', $type, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * 获取控件参数
     * @param $control
     * @param $control_args
     * @return array
     */
    public static function getControlProps($control, $control_args): array
    {
        if (!$control_args) {
            return [];
        }
        $control = strtolower($control);
        $props = [];
        $split = explode(';', $control_args);
        foreach ($split as $item) {
            $pos = strpos($item, ':');
            if ($pos === false) {
                continue;
            }
            $name = trim(substr($item, 0, $pos));
            $values = trim(substr($item, $pos + 1));
            // values = a:v,c:d
            $pos = strpos($values, ':');
            if ($pos !== false) {
                $options = explode(',', $values);
                $values = [];
                foreach ($options as $option) {
                    [$v, $n] = explode(':', $option);
                    if (in_array($control, ['select', 'selectmulti', 'treeselect', 'treemultiselect']) && $name == 'data') {
                        $values[] = ['value' => $v, 'name' => $n];
                    } else {
                        $values[$v] = $n;
                    }
                }
            }
            $props[$name] = $values;
        }
        return $props;

    }

    /**
     * 获取某个composer包的版本
     * @param string $package
     * @return string
     */
    public static function getPackageVersion(string $package): string
    {
        $installed_php = base_path('vendor/composer/installed.php');
        if (is_file($installed_php)) {
            $packages = include $installed_php;
        }
        return substr($packages['versions'][$package]['version'] ?? 'unknown  ', 0, -2);
    }


    /**
     * Reload webman
     * @return bool
     */
    public static function reloadWebman(): bool
    {
        if (function_exists('posix_kill')) {
            try {
                posix_kill(posix_getppid(), SIGUSR1);
                return true;
            } catch (Throwable $e) {
            }
        } else {
            Timer::add(1, function () {
                Worker::stopAll();
            });
        }
        return false;
    }

    /**
     * Pause file monitor
     * @return void
     */
    public static function pauseFileMonitor(): void
    {
        if (method_exists(Monitor::class, 'pause')) {
            Monitor::pause();
        }
    }

    /**
     * Resume file monitor
     * @return void
     */
    public static function resumeFileMonitor(): void
    {
        if (method_exists(Monitor::class, 'resume')) {
            Monitor::resume();
        }
    }

    /**
     * 创建目录
     * @param string $directory
     * @return void
     */
    public static function createDir(string $directory): void
    {
        if (!is_dir($directory)) {
            if (false === @mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Unable to create the "%s" directory', $directory));
            }
        }
        if (!is_writable($directory)) {
            throw new \RuntimeException(sprintf('Unable to write in the "%s" directory', $directory));
        }
    }
}
