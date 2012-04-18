<?php

namespace Bundles\Messages;
use Bundles\SQL\SQLBundle;
use Exception;
use e;

/**
 * Messages Bundle
 */
class Bundle extends SQLBundle {
	
	public function _on_message($data, $namespace = 'global', $type = 'info') {
		if(is_string($data))
			$data = array('message' => $data);

		if(!isset($data['type']))
			$data['type'] = $type;

		$message = $this->newMessage();
		if(!isset($data['namespace']))
			$data['namespace'] = $namespace;

		$data['status'] = 'active';
		
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

		if(empty($messages)) return array();

		/**
		 * Apply Conditions
		 */
		$messages->condition('status !=', 'cleared');
		if($namespace !== 'all')
			$messages->manual_condition('`namespace` IN ("global", "'.$namespace.'")');

		$this->__cached_messages = $messages;
		
		/**
		 * Mark the messages as viewed and clear them
		 */
		$return = array();
		foreach($messages as $message) {
			$return[] = $message->__toArray();
		}
		return $return;
	}

	public function _on_complete() {
		if(empty($this->__cached_messages))
			return;
		
		/**
		 * Apply Conditions
		 */
		 foreach($this->__cached_messages as $message) {
			switch($message->status) {
				case 'active':
					$message->status = 'to_clear';
					break;
				case 'to_clear':
					$message->status = 'cleared';
					break;
				case 'cleared':
					continue;
			}
			$message->viewed = 'yes';
			$message->save();
		}
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
