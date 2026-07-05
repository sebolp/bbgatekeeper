<?php
namespace sebo\bbgatekeeper\controller;

class whois_controller
{
	protected $auth;
	protected $template;
	protected $user;
	protected $request;
	protected $helper;
	protected $root_path;
	protected $php_ext;

	public function __construct($auth, $template, $user, $request, $helper, $root_path, $php_ext)
	{
		$this->auth      = $auth;
		$this->template  = $template;
		$this->user      = $user;
		$this->request   = $request;
		$this->helper    = $helper;
		$this->root_path = $root_path;
		$this->php_ext   = $php_ext;
	}

	public function handle()
	{
		if (!$this->auth->acl_get('a_'))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$ip = $this->request->variable('ip', '');
		if (!filter_var($ip, FILTER_VALIDATE_IP))
		{
			trigger_error('FORM_INVALID');
		}

		if (!function_exists('user_ipwhois'))
		{
			include($this->root_path . 'includes/functions_user.' . $this->php_ext);
		}

		$this->template->assign_var('WHOIS', user_ipwhois($ip));

		return $this->helper->render('bbgatekeeper_whois.html', $this->user->lang('WHO_IS_ONLINE'));
	}
}
