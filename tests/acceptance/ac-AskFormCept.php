<?php
/**
 * AnsPress ask form
 */

$I = new AcceptanceTester($scenario );
$I->wantTo('Check AnsPress form error message' );
$I->switch_user('user1', 'user1' );
$I->switch_user('user1', 'user1' );
$I->amOnPage( '/questions/ask/' );
$I->seeElement('#ask_form' );

$I->click('#ask_form .ap-btn-submit' );

$I->waitForText('This field is required', 30 );
$I->wantTo('Submit new question' );
$I->fillField([ 'name' => 'title' ], $I->questions['question1'] );
$I->fillTinyMceEditorById('description', 'Fusce iaculis condimentum nisi, nec commodo eros molestie at. Nullam libero erat, sollicitudin eu condimentum sit amet, rhoncus ut lacus. Integer vulputate nibh et diam sagittis in dictum mauris dapibus. ' );

$I->click('#ask_form .ap-btn-submit' );
$I->waitForJS( 'return jQuery.active == 0;',60 );
$I->amOnPage( '/questions/' );
$I->see($I->questions['question1'] );
//$I->makeScreenshot('questions_page' );

$I->wantTo('Check categories page' );
$I->amOnPage( '/questions/categories/' );
$I->see('Awesome_Category');

$I->wantTo('Check category page' );
$I->amOnPage( '/questions/category/awesome_category/' );
$I->see('Awesome_Category');
