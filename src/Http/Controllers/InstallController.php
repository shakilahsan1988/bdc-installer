<?php

namespace Bdc\Installer\Http\Controllers;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;


class InstallController extends Controller
{
    private $databaseManager;
    private $logger;

    public function __construct(DatabaseManager $databaseManager)
    {
        $this->logger = new Logger(storage_path() . '/logs/' . 'install.log');
        $this->databaseManager = $databaseManager;
    }


    public function checkConfiguration()
    {
        $route_value = 0;
        $resource_value = 0;
        $public_value = 0;
        $storage_value = 0;
        $env_value = 0;

        $resource_prem = substr(sprintf('%o', fileperms(base_path('resources'))), -4);
        if ($resource_prem == '0777' || $resource_prem == '0775') {
            $resource_value = 1;
        }
        $public_prem = substr(sprintf('%o', fileperms(base_path('public'))), -4);
        if ($public_prem == '0777' || $public_prem == '0775' || $public_prem == '0750') {
            $public_value = 1;
        }
        $storage_prem = substr(sprintf('%o', fileperms(base_path('storage'))), -4);
        if ($storage_prem == '0777' || $storage_prem == '0775') {
            $storage_value = 1;
        }
        $env_prem = substr(sprintf('%o', fileperms(base_path('.env'))), -4);
        if ($env_prem == '0777' || $env_prem == '0666' || $env_prem == '0644' || $env_prem == '0775' || $env_prem == '0664') {
            $env_value = 1;
        }
        if (file_exists(storage_path('installed'))) {
            return redirect('/');
        }
        return view('views::check_config', compact('route_value', 'resource_value', 'public_value', 'storage_value', 'env_value'));
    }


    public function checkValidation(Request $request)
    {
        if ($this->phpversion() > 7.0 && $this->mysqli() == 1 && $this->curl_version() == 1 && $this->allow_url_fopen() == 1 && $this->openssl() == 1 && $this->zip() == 1 && $this->pdo() == 1 && $this->bcmath() == 1 && $this->ctype() == 1 && $this->fileinfo() == 1 && $this->mbstring() == 1 && $this->tokenizer() == 1 && $this->xml() == 1 && $this->json() == 1) {
            session()->put('validated', 'Yes');
            return redirect(route('BdcInstaller::installation.process'));
        }
        session()->forget('validated');
        return redirect(route('BdcInstaller::check.configuration'));
    }


    public function installationProcess()
    {

        if (file_exists(storage_path('installed'))) {
            return redirect('/');
        }

        if (session()->has('validated')) {
            return view('views::installation');
        }
        return redirect(route('BdcInstaller::check.configuration'));
    }


    public function phpversion()
    {
        return phpversion();
    }

    public function mysqli()
    {
        return extension_loaded('mysqli');
    }

    public function curl_version()
    {
        return function_exists('curl_version');
    }

    public function allow_url_fopen()
    {
        return ini_get('allow_url_fopen');
    }

    public function openssl()
    {
        return extension_loaded('openssl');
    }

    public function pdo()
    {
        return extension_loaded('pdo');
    }

    public function zip()
    {
        return extension_loaded('zip');
    }

    public function bcmath()
    {
        return extension_loaded('bcmath');
    }

    public function ctype()
    {
        return extension_loaded('ctype');
    }

    public function fileinfo()
    {
        return extension_loaded('fileinfo');
    }

    public function mbstring()
    {
        return extension_loaded('mbstring');
    }

    public function tokenizer()
    {
        return extension_loaded('tokenizer');
    }

    public function xml()
    {
        return extension_loaded('xml');
    }

    public function json()
    {
        return extension_loaded('json');
    }

    public function processStore(Request $request)
    {
        $request->validate([
            'db_host' => 'required',
            'db_user' => 'required',
            'db_name' => 'required',
            'db_password' => 'nullable',
            'db_port' => 'nullable|integer',
        ]);

        if (!$this->checkDatabaseConnection($request)) {
            $this->logger->log('End with', 'Database credential is not correct!');
            $this->logger->log('', '==============END=============');
            return Redirect::back()->withErrors('Database credential is not correct!');
        }

        $this->logger->log('ENV', 'Write start');
        $results = $this->saveENV($request);
        $this->logger->log('ENV', 'Write END');
        $this->logger->log('ENV Write', $results ? 'True' : 'False');

        $this->logger->log('Step-2', 'Redirecting to database insert');

        return redirect()->route('BdcInstaller::database');
    }

    public function is_valid_domain_name($url)
    {
        try {
            $parseUrl = parse_url(trim($url));
            if (isset($parseUrl['host'])) {
                $host = $parseUrl['host'];
            } else {
                $path = explode('/', $parseUrl['path']);
                $host = $path[0];
            }
            $domain_name = trim($host);

            return (preg_match("/^(?:[-A-Za-z0-9]+\.)+[A-Za-z]{2,6}$/i", $domain_name));
        } catch (\Exception $e) {
            return false;
        }
    }

    public function saveInfoInFile($url, $purchase_code)
    {
        $this->logger->log('Step-1', 'Start install info save from saveInfoInFile');
        $infoFile = storage_path('info');
        if (file_exists($infoFile)) {
            unlink($infoFile);
        }

        $data = json_encode([
            'd' => base64_encode($this->get_domain_name($url)),
            'i' => date('ymdhis'),
            'p' => base64_encode($purchase_code),
            'u' => date('ymdhis'),
        ]);

        file_put_contents($infoFile, $data);
        $this->logger->log('Step-1', 'END install info save from saveInfoInFile');
    }

    public function database()
    {
        set_time_limit(0);
        $this->logger->log('STEP-2', 'Start from database method');
        $this->logger->log('Migration & seed', 'Start');
        //$response = $this->databaseManager->migrateAndSeed();

        $response = DB::unprepared(file_get_contents(database_path('db.sql')));

        $this->logger->log('Migration & seed', 'END');
        $this->logger->log('Migration & seed response ', $response);
        if ($response == true) {
            try {
//                $this->logger->log('LQS file', 'Local sql get content start');
//                $lqs = file_get_contents(config('app.sql_path'));
//                $this->logger->log('LQS file', 'Local sql get content END');
//
//                $this->logger->log('SQL import', 'START');
//                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
//                if ($lqs != null && $lqs != "") {
//                    DB::unprepared($lqs);
//                }
//                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
//                $this->logger->log('SQL import', 'END');

                $this->logger->log('Installed file', 'Write Start');
                $installedLogFile = storage_path('installed');

                $data = json_encode([
                    'd' => base64_encode($this->get_domain_name(request()->fullUrl())),
                    'i' => date('ymdhis'),
                    'u' => date('ymdhis'),
                ]);

                if (!file_exists($installedLogFile)) {
                    file_put_contents($installedLogFile, $data);
                }

                $this->logger->log('Installed file', 'Write END');

                $this->logger->log('End with', 'Successfully installed');
                $this->logger->log('', '==============END=============');
                return redirect('/');
            } catch (\Exception $e) {

                DB::rollBack();
                $this->logger->log('End with', 'DB import failed');
                $this->logger->log('Exception', $e->getMessage());
                $this->logger->log('', '==============END=============');
                return Redirect::back()->withErrors('Something went wrong!');
            }
        } else {
            $this->logger->log('End with', 'Migration & seed failed');
            $this->logger->log('', '==============END=============');
            return Redirect::back()->withErrors($response['message']);
        }
    }

    function get_domain_name($url)
    {
        $parseUrl = parse_url(trim($url));
        if (isset($parseUrl['host'])) {
            $host = $parseUrl['host'];
        } else {
            $path = explode('/', $parseUrl['path']);
            $host = $path[0];
        }
        return trim($host);
    }

    public function saveENV(Request $request)
    {
        $this->logger->log('ENV', 'Write start from saveENV');
        $env_val['APP_KEY'] = 'base64:' . base64_encode(Str::random(32));
        $env_val['APP_URL'] = $request->app_url;
        $env_val['DB_HOST'] = $request->db_host;
        $env_val['DB_DATABASE'] = $request->db_name;
        $env_val['DB_USERNAME'] = $request->db_user;
        $env_val['DB_PASSWORD'] = $request->db_password;
        $env_val['MAIL_HOST'] = $request->mail_host;
        $env_val['MAIL_PORT'] = $request->mail_port;
        $env_val['MAIL_USERNAME'] = $request->mail_username;
        $env_val['MAIL_PASSWORD'] = $request->mail_password;

        return $this->setEnvValue($env_val);
    }

    public function setEnvValue($values)
    {
        $this->logger->log('ENV', 'Write start from setEnvValue');
        if (count($values) > 0) {
            foreach ($values as $envKey => $envValue) {
                $this->setEnvironmentValue($envKey, $envValue);
            }
        }

        $this->logger->log('ENV', 'Write setEnvValue successfully');
        return true;
    }

    function setEnvironmentValue($envKey, $envValue)
    {
        try {
            $this->logger->log('ENV Write start', $envKey . '=>' . $envValue);
            $envFile = app()->environmentFilePath();
            $str = file_get_contents($envFile);
            $str .= "\n"; // In case the searched variable is in the last line without \n
            $keyPosition = strpos($str, "{$envKey}=");
            if ($keyPosition) {
                if (PHP_OS_FAMILY === 'Windows') {
                    $endOfLinePosition = strpos($str, "\n", $keyPosition);
                } else {
                    $endOfLinePosition = strpos($str, PHP_EOL, $keyPosition);
                }
                $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);
                $envValue = str_replace(chr(92), "\\\\", $envValue);
                $envValue = str_replace('"', '\"', $envValue);
                $newLine = "{$envKey}=\"{$envValue}\"";
                if ($oldLine != $newLine) {
                    $str = str_replace($oldLine, $newLine, $str);
                    $str = substr($str, 0, -1);
                    $fp = fopen($envFile, 'w');
                    fwrite($fp, $str);
                    fclose($fp);
                }
            }

            $this->logger->log('ENV Write END', $envKey . '=>' . $envValue);
            return true;
        } catch (\Exception $e) {
            $this->logger->log('ENV', 'Write setEnvValue failed');
            $this->logger->log('Exception', $e->getMessage());
            return false;
        }
    }

    private function checkDatabaseConnection(Request $request)
    {
        $connection = 'mysql';

        $settings = config("database.connections.mysql");

        config([
            'database' => [
                'default' => 'mysql',
                'connections' => [
                    'mysql' => array_merge($settings, [
                        'driver' => 'mysql',
                        'host' => $request->db_host,
                        'port' => '3306',
                        'database' => $request->db_name,
                        'username' => $request->db_user,
                        'password' => $request->db_password,
                    ]),
                ],
            ],
        ]);

        DB::purge();
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
