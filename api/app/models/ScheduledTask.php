<?php
namespace models;

class ScheduledTask extends \Core\Model
{
	protected $guarded = array();
	protected $primaryKey = '_id';

	public function app() {
		return $this->belongsTo('models\App');
	}

	/**
	 * Current app scope
	 * @example
	 *     ScheduledTask::current()->delete()
	 */
	public function scopeCurrent($query) {
		return $query->where('app_id', App::currentId());
	}

	public function getCommand() {
		$shortcuts = array(
			'hourly'  => '0 * * * *',
			'daily'   => '0 0 * * *',
			'monthly' => '0 0 1 * *',
			'weekly'  => '0 0 * * 0'
		);
		$schedule = preg_match('/[a-z]/', $this->schedule) ? $shortcuts[$this->schedule] : $this->schedule;

		$protocol = (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http');
		$public_url = $protocol . '://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['SCRIPT_NAME'] . '/' . $this->task;

		// TODO: redirect output to application log file.
		// https://github.com/doubleleft/dl-api/issues/37
		return $schedule . ' ' . "curl -XGET -H 'X-App-Id: {$this->app_id}' -H 'X-App-Key: {$this->app->keys[0]->key}' '{$public_url}' 2>&1 /dev/null";
	}

	public function toArray() {
		$arr = parent::toArray();
		$arr['command'] = $this->getCommand();
		return $arr;
	}

	public static function install() {
		exec('crontab ' . __DIR__ . '/../storage/crontabs/*.cron', $output, $return_code);

		if (!empty($output)) {
			throw new Exception(json_encode($output));
		}

		return $return_code === 0;

		// $tasks = array();
		// static::all()->each(function($task) use (&$tasks) {
		// 	array_push($tasks, $task->toString());
		// });
		// file_put_contents(__DIR__ . '/../storage/crontab', join("\n", $tasks));
	}

}



