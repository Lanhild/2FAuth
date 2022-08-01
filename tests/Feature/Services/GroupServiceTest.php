<?php

namespace Tests\Feature\Services;

use App\Models\Group;
use App\Models\TwoFAccount;
use Tests\FeatureTestCase;
use App\Facades\Groups;
use App\Facades\Settings;


/**
 * @covers \App\Services\GroupService
 */
class GroupServiceTest extends FeatureTestCase
{
    /**
     * App\Models\Group $groupOne, $groupTwo
     */
    protected $groupOne, $groupTwo;


    /**
     * App\Models\Group $twofaccountOne, $twofaccountTwo
     */
    protected $twofaccountOne, $twofaccountTwo;

    private const NEW_GROUP_NAME = 'MyNewGroup';
    private const TWOFACCOUNT_COUNT = 2;
    private const ACCOUNT = 'account';
    private const SERVICE = 'service';
    private const SECRET = 'A4GRFHVVRBGY7UIW';
    private const ALGORITHM_CUSTOM = 'sha256';
    private const DIGITS_CUSTOM = 7;
    private const PERIOD_CUSTOM = 40;
    private const IMAGE = 'https%3A%2F%2Fen.opensuse.org%2Fimages%2F4%2F44%2FButton-filled-colour.png';
    private const ICON = 'test.png';
    private const TOTP_FULL_CUSTOM_URI = 'otpauth://totp/'.self::SERVICE.':'.self::ACCOUNT.'?secret='.self::SECRET.'&issuer='.self::SERVICE.'&digits='.self::DIGITS_CUSTOM.'&period='.self::PERIOD_CUSTOM.'&algorithm='.self::ALGORITHM_CUSTOM.'&image='.self::IMAGE;



    /**
     * @test
     */
    public function setUp() : void
    {
        parent::setUp();

        $this->groupOne = new Group;
        $this->groupOne->name = 'MyGroupOne';
        $this->groupOne->save();

        $this->groupTwo = new Group;
        $this->groupTwo->name = 'MyGroupTwo';
        $this->groupTwo->save();

        $this->twofaccountOne = new TwoFAccount;
        $this->twofaccountOne->legacy_uri = self::TOTP_FULL_CUSTOM_URI;
        $this->twofaccountOne->service = self::SERVICE;
        $this->twofaccountOne->account = self::ACCOUNT;
        $this->twofaccountOne->icon = self::ICON;
        $this->twofaccountOne->otp_type = 'totp';
        $this->twofaccountOne->secret = self::SECRET;
        $this->twofaccountOne->digits = self::DIGITS_CUSTOM;
        $this->twofaccountOne->algorithm = self::ALGORITHM_CUSTOM;
        $this->twofaccountOne->period = self::PERIOD_CUSTOM;
        $this->twofaccountOne->counter = null;
        $this->twofaccountOne->save();

        $this->twofaccountTwo = new TwoFAccount;
        $this->twofaccountTwo->legacy_uri = self::TOTP_FULL_CUSTOM_URI;
        $this->twofaccountTwo->service = self::SERVICE;
        $this->twofaccountTwo->account = self::ACCOUNT;
        $this->twofaccountTwo->icon = self::ICON;
        $this->twofaccountTwo->otp_type = 'totp';
        $this->twofaccountTwo->secret = self::SECRET;
        $this->twofaccountTwo->digits = self::DIGITS_CUSTOM;
        $this->twofaccountTwo->algorithm = self::ALGORITHM_CUSTOM;
        $this->twofaccountTwo->period = self::PERIOD_CUSTOM;
        $this->twofaccountTwo->counter = null;
        $this->twofaccountTwo->save();
    }


    /**
     * @test
     */
    public function test_getAll_returns_a_collection()
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, Groups::getAll());
    }


    /**
     * @test
     */
    public function test_getAll_adds_pseudo_group_on_top_of_user_groups()
    {
        $groups = Groups::getAll();
        
        $this->assertEquals(0, $groups->first()->id);
        $this->assertEquals(__('commons.all'), $groups->first()->name);
    }


    /**
     * @test
     */
    public function test_getAll_returns_pseudo_group_with_all_twofaccounts_count()
    {
        $groups = Groups::getAll();
        
        $this->assertEquals(self::TWOFACCOUNT_COUNT, $groups->first()->twofaccounts_count);
    }


    /**
     * @test
     */
    public function test_create_persists_and_returns_created_group()
    {
        $newGroup = Groups::create(['name' => self::NEW_GROUP_NAME]);
        
        $this->assertDatabaseHas('groups', ['name' => self::NEW_GROUP_NAME]);
        $this->assertInstanceOf(\App\Models\Group::class, $newGroup);
        $this->assertEquals(self::NEW_GROUP_NAME, $newGroup->name);
    }


    /**
     * @test
     */
    public function test_update_persists_and_returns_updated_group()
    {
        $this->groupOne = Groups::update($this->groupOne, ['name' => self::NEW_GROUP_NAME]);
        
        $this->assertDatabaseHas('groups', ['name' => self::NEW_GROUP_NAME]);
        $this->assertInstanceOf(\App\Models\Group::class, $this->groupOne);
        $this->assertEquals(self::NEW_GROUP_NAME, $this->groupOne->name);
    }


    /**
     * @test
     */
    public function test_delete_a_groupId_clear_db_and_returns_deleted_count()
    {
        $deleted = Groups::delete($this->groupOne->id);
        
        $this->assertDatabaseMissing('groups', ['id' => $this->groupOne->id]);
        $this->assertEquals(1, $deleted);
    }


    /**
     * @test
     */
    public function test_delete_an_array_of_ids_clear_db_and_returns_deleted_count()
    {
        $deleted = Groups::delete([$this->groupOne->id, $this->groupTwo->id]);
        
        $this->assertDatabaseMissing('groups', ['id' => $this->groupOne->id]);
        $this->assertDatabaseMissing('groups', ['id' => $this->groupTwo->id]);
        $this->assertEquals(2, $deleted);
    }


    /**
     * @test
     */
    public function test_delete_default_group_reset_defaultGroup_setting()
    {
        Settings::set('defaultGroup', $this->groupOne->id);

        $deleted = Groups::delete($this->groupOne->id);
        
        $this->assertDatabaseHas('options', [
            'key' => 'defaultGroup',
            'value' => 0
        ]);
    }


    /**
     * @test
     */
    public function test_delete_active_group_reset_activeGroup_setting()
    {
        Settings::set('rememberActiveGroup', true);
        Settings::set('activeGroup', $this->groupOne->id);
        
        $deleted = Groups::delete($this->groupOne->id);
        
        $this->assertDatabaseHas('options', [
            'key' => 'activeGroup',
            'value' => 0
        ]);
    }


    /**
     * @test
     */
    public function test_assign_a_twofaccountid_to_a_specified_group_persists_the_relation()
    {
        
        Groups::assign($this->twofaccountOne->id, $this->groupOne);
        
        $this->assertDatabaseHas('twofaccounts', [
            'id' => $this->twofaccountOne->id,
            'group_id' => $this->groupOne->id,
        ]);
    }


    /**
     * @test
     */
    public function test_assign_multiple_twofaccountid_to_a_specified_group_persists_the_relation()
    {
        Groups::assign([$this->twofaccountOne->id, $this->twofaccountTwo->id], $this->groupOne);
        
        $this->assertDatabaseHas('twofaccounts', [
            'id' => $this->twofaccountOne->id,
            'group_id' => $this->groupOne->id,
        ]);
        $this->assertDatabaseHas('twofaccounts', [
            'id' => $this->twofaccountTwo->id,
            'group_id' => $this->groupOne->id,
        ]);
    }


    /**
     * @test
     */
    public function test_assign_a_twofaccountid_to_no_group_assigns_to_default_group()
    {
        Settings::set('defaultGroup', $this->groupTwo->id);
        
        Groups::assign($this->twofaccountOne->id);
        
        $this->assertDatabaseHas('twofaccounts', [
            'id' => $this->twofaccountOne->id,
            'group_id' => $this->groupTwo->id,
        ]);
    }


    /**
     * @test
     */
    public function test_assign_a_twofaccountid_to_no_group_assigns_to_active_group()
    {
        Settings::set('defaultGroup', -1);
        Settings::set('activeGroup', $this->groupTwo->id);
        
        Groups::assign($this->twofaccountOne->id);
        
        $this->assertDatabaseHas('twofaccounts', [
            'id' => $this->twofaccountOne->id,
            'group_id' => $this->groupTwo->id,
        ]);
    }


    /**
     * @test
     */
    public function test_assign_a_twofaccountid_to_missing_active_group_does_not_fails()
    {
        Settings::set('defaultGroup', -1);
        Settings::set('activeGroup', 100000);
        
        Groups::assign($this->twofaccountOne->id);
        
        $this->assertDatabaseHas('twofaccounts', [
            'id' => $this->twofaccountOne->id,
            'group_id' => null,
        ]);
    }


    /**
     * @test
     */
    public function test_getAccounts_returns_accounts()
    {
        Groups::assign([$this->twofaccountOne->id, $this->twofaccountTwo->id], $this->groupOne);
        $accounts = Groups::getAccounts($this->groupOne);
        
        $this->assertEquals(2, $accounts->count());
    }

}