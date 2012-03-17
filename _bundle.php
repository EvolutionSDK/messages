<?php

namespace Bundles\Messages;
use Bundles\SQL\SQLBundle;
use Exception;
use e;

/**
 * Messages Bundle
 */
class Bundle extends SQLBundle {
	
	public function _on_message($data, $namespace = 'global') {
		$message = $this->newMessage();
		if(!isset($data['namespace']))
			$data['namespace'] = $namespace;
		
		$message->save($data);

		$member = e::$members->currentMember();
		if($message->id > 0 && $member) {
			$message->linkMembersMember($member);
		}

		if($message->id > 0 && e::$session->_id > 0) {
			$message->linkSessionSession(e::$session->_id);
		}
	}
	
	public function currentMessages($namespace = 'all') {
		
		$member = e::$members->currentMember();
		
		if($member) $messages = $member->getMessagesMessages();
		else $messages = e::$session->getMessagesMessages();
		
		/**
		 * Apply Conditions
		 */
		$messages->condition('status', 'active')->condition('viewed', 'no');
		if($namespace !== 'all')
			$messages->manual_condition('`namespace` IN ("global", "'.$namespace.'")');
		
		/**
		 * Mark the messages as viewed and clear them
		 */
		$return = array();
		foreach($messages as $message) {
			$message->status = 'cleared';
			$message->viewed = 'yes';
			$message->save();
			$return[] = $message->__toArray();
		}
		
		return $return;
	}
	
	public function printMessages($namespace = 'none') {
		
		if(!defined('BUNDLE_MESSAGES_PRINTED_STYLE')) {
			echo <<<_
<style>
ul.validator-messages {
	margin: 0;
	padding: 0;
	list-style-type: none;
}
ul.validator-messages li {
	margin: 0 0 1em 0;
	padding: 0.5em;
	
	border: 1px solid #888;
	background: #eee;
	color: #666;
}
ul.validator-messages li.message-error {
	border: 1px solid #600;
	background: #fcc;
	color: #600;
}
ul.validator-messages li span.field {
	font-weight: bold;
}
</style>
_;
			define('BUNDLE_MESSAGES_PRINTED_STYLE', 1);
		}
		echo '<ul class="validator-messages">';
		
		
		$member = e::$members->currentMember();
		
		if($member) $messages = $member->getMessages();
		else $messages = e::$session->getMessages();
			
		$messages = $messages->condition('status', 'active')->condition('viewed', 'no');
		$messages = $messages->manual_condition('`namespace` IN ("global", "'.$namespace.'")');
		
		foreach($messages as $message) {
			$message->status = 'cleared';
			$message->viewed = 'yes';
			echo '<li class="message-' . $message->type . '">' . $message->message . '</li>';
		}
		echo '</ul>';
	}
	
}
