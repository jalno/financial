<?php
namespace packages\financial\PayPort;

class AlreadyVerified extends VerificationException{
	protected $message = 'alreadyverified';
}