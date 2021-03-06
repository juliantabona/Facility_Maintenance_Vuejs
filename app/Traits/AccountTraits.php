<?php

namespace App\Traits;

use DB;
use App\Sms;
use App\User;
use App\Phone;
use App\Invoice;
use App\Quotation;
use App\Document;
use App\Traits\CountryTraits;
use App\Notifications\AccountCreated;
use App\Notifications\AccountApproved;
//  Resources
use App\Http\Resources\Account as AccountResource;
use App\Http\Resources\Accounts as AccountsResource;

trait AccountTraits
{

    use CountryTraits;


    /*  convertToApiFormat() method:
     *
     *  Converts to the appropriate Api Response Format
     *
     */
    public function convertToApiFormat($accounts = null)
    {

        try {

            if( $accounts ){

                //  Transform the accounts
                return new AccountsResource($accounts);

            }else{

                //  Transform the account
                return new AccountResource($this);

            }

        } catch (\Exception $e) {

            //  Log the error
            return oq_api_notify_error('Query Error', $e->getMessage(), 404);

        }
    }

    /*  getAccounts() method:
     *
     *  This is used to return companies
     *
     */
    public function getAccounts( $options = [] )
    {
        /************************************
        *  CHECK IF THE USER IS AUTHORIZED  *
        /************************************/

        try {

            //  If we have provided the users id
            if( isset($options['user_id']) && !empty(isset($options['user_id'])) ){
                
                //  Get the specified user
                $user = User::find( $options['user_id'] );
            
                //  Filter the companies by the user type
                $userTypes = request('userTypes');

                if( isset($userTypes) && !empty($userTypes) ){
                    
                    if($userTypes == 'admin'){
                        //  Get companies where this user is an admin
                        $companies = $user->companiesWhereUserIsAdmin;
                    }elseif($userTypes == 'staff'){
                        //  Get companies where this user is an staff member
                        $companies = $user->companiesWhereUserIsStaff;
                    }elseif($userTypes == 'customer'){
                        //  Get companies where this user is an customer
                        $companies = $user->companiesWhereUserIsClient;
                    }elseif($userTypes == 'vendor'){
                        //  Get companies where this user is an vendor
                        $companies = $user->companiesWhereUserIsVendor;
                    }else{
                        /*  Incase $userTypes is a list e.g) admin,staff ... e.t.c
                         *  This means we want companies where the user plays anyone
                         *  of those roles.
                         */
                        $userTypes = explode(',', $userTypes );

                        //  If we have atleast one userType
                        if (count($userTypes)) {
                            //  Get companies related to the listed user types
                            $companies = $user->companies()->whereHas('users', function ($query) use($userTypes) {
                                            $query->whereIn('user_allocations.type', $userTypes);
                                        })->get();
                        }
                    }

                }else{

                    //  Get the specified companies for this user
                    $companies = $user->companies()->get();
                }

            }else{

                //  Get all the companies
                $companies = $this->all();

            }

            if( $companies ){

                //  Transform the companies
                return new AccountsResource($companies);

            }else{

                //  Otherwise we don't have a resource to return
                return oq_api_notify_no_resource();
            
            }

        } catch (\Exception $e) {

            //  Log the error
            return oq_api_notify_error('Query Error', $e->getMessage(), 404);

        }
    }




























    /*  initiateGetAll() method:
     *
     *  This is used to return a pagination of company results.
     *
     */
    public function initiateGetAll($options = array())
    {
        //  Default settings
        $defaults = array(
            'paginate' => true,
        );

        //  Replace defaults with any provided options
        $config = array_merge($defaults, $options);

        //  If we have the paginate value provided
        $requestPagination = request('paginate');
        
        if (isset($requestPagination) && ($requestPagination == 0 || $requestPagination == 1)) {
            $config['paginate'] = ($requestPagination == 1) ? true : false;
        }

        //  Current authenticated user
        $auth_user = auth('api')->user();


        //  Get the company instance to gain access to all the companies
        $companies = $this;

        /*******************************
         *  FILTER BY USER IDS         *
         ******************************/
        $userIds = strtolower(request('userIds'));

        //  If user indicated to only return companies related to a specific user id
        if( isset($userIds) && !empty( $userIds ) ){

            //  Incase $userIds is a list e.g) 1,2,3 ... e.t.c
            $userIds = explode(',', $userIds );

            //  If we have atleast one user id
            if (count($userIds)) {
                //  Get companies related to the listed user ids
                $companies = $companies->whereHas('users', function ($query) use($userIds) {
                                    $query->whereIn('user_allocations.user_id', $userIds);
                                });
            }

        }

        /*******************************
         *  FILTER BY USER TYPES       *
         ******************************/
        $userTypes = strtolower(request('userTypes'));

        //  If user indicated to only return companies related to a specific user type
        if( isset($userTypes) && !empty( $userTypes ) ){

            //  Incase $userTypes is a list e.g) admin,staff, ... e.t.c
            $userTypes = explode(',', $userTypes );

            //  If we have atleast one user type
            if (count($userTypes)) {
                //  Get companies related to the listed user types
                $companies = $companies->whereHas('users', function ($query) use($userTypes) {
                                    $query->whereIn('user_allocations.type', $userTypes);
                                });
            }

        }

        /*  To avoid sql order_by error for ambigious fields e.g) created_at
         *  we must specify the order_join.
         *
         *  Order joins help us when using the "advancedFilter()" method. Usually
         *  we need to specify the joining table so that the system is not confused
         *  by similar column names that exist on joining tables. E.g) the column
         *  "created_at" can exist in multiple table and the system might not know
         *  whether the "order_by" is for table_1 created_at or table 2 created_at.
         *  By specifying this we end up with "table_1.created_at"
         *
         *  If we don't have any special order_joins, lets default it to nothing
         */

        $order_join = 'companies';

        try {
            //  Get all and trashed
            if (request('withtrashed') == 1) {
                //  Run query
                $companies = $companies->withTrashed()->advancedFilter(['order_join' => $order_join, 'paginate' => $config['paginate']]);
            //  Get only trashed
            } elseif (request('onlytrashed') == 1) {
                //  Run query
                $companies = $companies->onlyTrashed()->advancedFilter(['order_join' => $order_join, 'paginate' => $config['paginate']]);
            //  Get all except trashed
            } else {
                //  Run query
                $companies = $companies->advancedFilter(['order_join' => $order_join, 'paginate' => $config['paginate']]);
            }

            //  If we only want to know how many were returned
            if( request('count') == 1 ){
                //  If the companies are paginated
                if($config['paginate']){
                    $companies = $companies->total() ?? 0;
                //  If the companies are not paginated
                }else{
                    $companies = $companies->count() ?? 0;
                }
            }else{
                //  If we are not paginating then
                if (!$config['paginate']) {
                    //  Get the collection
                    $companies = $companies->get();
                }

                //  If we have any companies so far
                if ($companies) {
                    //  Eager load other relationships wanted if specified
                    if (strtolower(request('connections'))) {
                        $companies->load(oq_url_to_array(strtolower(request('connections'))));
                    }
                }
            }

            //  Action was executed successfully
            return ['success' => true, 'response' => $companies];

        } catch (\Exception $e) {
            //  Log the error
            $response = oq_api_notify_error('Query Error', $e->getMessage(), 404);

            //  Return the error response
            return ['success' => false, 'response' => $response];
        }
    }

    public function initiateGetStaff($options = array())
    {
        //  Default settings
        $defaults = array(
            'paginate' => false,
        );

        //  Replace defaults with any provided options
        $config = array_merge($defaults, $options);

        //  Current authenticated user
        $auth_user = auth('api')->user();

        /*
         *  $allocation = all, company, branch
        /*
         *  The $allocation variable is used to determine where the data is being
         *  pulled from. The user may request data from three possible sources.
         *  1) Data may come from the associated authenticated user branch
         *  2) Data may come from the associated authenticated user company
         *  3) Data may come from the whole bucket meaning outside the scope of the
         *     authenticated user. This means we can access all possible records
         *     available. This is usually useful for users acting as superadmins.
         */
        $allocation = strtolower(request('allocation'));

        //  Apply filter by allocation
        if ($allocation == 'all') {
            /********************************************************
            *  CHECK IF THE USER IS AUTHORIZED TO ALL STAFF         *
            /********************************************************/

            //  Get the current company instance
            $model = $this;
        } elseif ($allocation == 'branch') {
            /*************************************************************
            *  CHECK IF THE USER IS AUTHORIZED TO GET BRANCH STAFF    *
            /*************************************************************/

            // Only get companies associated to the company branch
            $model = $auth_user->companyBranch;
        } else {
            /***********************************************************
            *  CHECK IF THE USER IS AUTHORIZED TO GET COMPANY STAFF    *
            /***********************************************************/

            //  Only get companies associated to the company
            $model = $auth_user->company;
        }

        $staff = $model->userStaff();

        /*  To avoid sql order_by error for ambigious fields e.g) created_at
         *  we must specify the order_join.
         *
         *  Order joins help us when using the "advancedFilter()" method. Usually
         *  we need to specify the joining table so that the system is not confused
         *  by similar column names that exist on joining tables. E.g) the column
         *  "created_at" can exist in multiple table and the system might not know
         *  whether the "order_by" is for table_1 created_at or table 2 created_at.
         *  By specifying this we end up with "table_1.created_at"
         *
         *  If we don't have any special order_joins, lets default it to nothing
         */

        $order_join = 'user_directory';

        try {
            //  Get all and trashed
            if (request('withtrashed') == 1) {
                //  Run query
                $staff = $staff->withTrashed()->advancedFilter(['order_join' => $order_join, 'paginate' => $config['paginate']]);
            //  Get only trashed
            } elseif (request('onlytrashed') == 1) {
                //  Run query
                $staff = $staff->onlyTrashed()->advancedFilter(['order_join' => $order_join, 'paginate' => $config['paginate']]);
            //  Get all except trashed
            } else {
                //  Run query
                $staff = $staff->advancedFilter(['order_join' => $order_join, 'paginate' => $config['paginate']]);
            }
            //  If we only want to know how many were returned
            if( request('count') == 1 ){
                //  If the staff are paginated
                if($config['paginate']){
                    $staff = $staff->total() ?? 0;
                //  If the staff are not paginated
                }else{
                    $staff = $staff->count() ?? 0;
                }
            }else{
                //  If we are not paginating then
                if (!$config['paginate']) {
                    //  Get the collection
                    $staff = $staff->get();
                }

                //  If we have any staff so far
                if ($staff) {
                    //  Eager load other relationships wanted if specified
                    if (strtolower(request('connections'))) {
                        $staff->load(oq_url_to_array(strtolower(request('connections'))));
                    }
                }
            }

            //  Action was executed successfully
            return ['success' => true, 'response' => $staff];
        } catch (\Exception $e) {
            //  Log the error
            $response = oq_api_notify_error('Query Error', $e->getMessage(), 404);

            //  Return the error response
            return ['success' => false, 'response' => $response];
        }
    }

    public function initiateCreate()
    {
        //  Current authenticated user
        $auth_user = auth('api')->user();

        /*******************************************************
         *   CHECK IF USER HAS PERMISSION TO CREATE A COMPANY  *
         ******************************************************/

        /*********************************************
         *   VALIDATE COMPANY INFORMATION            *
         ********************************************/
         
        try {
            $template = [
                'name' => request('name'),
                'abbreviation' => request('abbreviation') ?? null,
                'description' => request('description') ?? null,
                'date_of_incorporation' => request('date_of_incorporation') ?? null,
                'type' => request('type') ?? null,
                'industry' => request('industry') ?? null,
                'address' => request('address') ?? null,
                'country' => request('country') ?? null,
                'province' => request('province') ?? null,
                'city' => request('city') ?? null,
                'postal_or_zipcode' => request('postal_or_zipcode') ?? null,
                'email' => request('email') ?? null,
                'additional_email' => request('additional_email') ?? null,
                'website_link' => request('website_link') ?? null,
                'facebook_link' => request('facebook_link') ?? null,
                'twitter_link' => request('twitter_link') ?? null,
                'linkedin_link' => request('linkedin_link') ?? null,
                'instagram_link' => request('instagram_link') ?? null,
                'bio' => request('bio') ?? null,
            ];

            //  Create the company
            $company = $this->create($template)->fresh();

            //  If the company was created successfully
            if ($company) {

                //  Check whether or not this company has a branch otherwise create a new one
                $this->checkOrCreateNewBranch($company);

                //  Check whether or not to update the auth user as belonging to this company
                $this->checkAndAssignAccountToAuth($company);

                //  Check whether or not the auth company has a relationship with the created company e.g) customer/supplier
                $this->checkAndCreateRelationship($company);

                //  Check whether or not the company has any logo to upload
                $this->checkAndUploadLogo($company);

                //  Check if the company has an sms credit record otherwise create a new credit record
                $this->checkAndCreateSmsCredit($company);

                //  Check if the company has any settings otherwise create new settings
                $this->checkAndCreateSettings($company);

                //  Check if the company has any phones to add and replace
                $this->checkAndUpdatePhones($company);

                //  refetch the created company
                $company = $company->fresh();

                /*****************************
                 *   SEND NOTIFICATIONS      *
                 *****************************/
                $auth_user->notify(new AccountCreated($company));

                /*****************************
                 *   RECORD ACTIVITY         *
                 *****************************/

                $status = 'created';

                $companyCreatedActivity = oq_saveActivity($company, $auth_user, $status, $template);

                //  Action was executed successfully
                return ['success' => true, 'response' => $company];
            }
        } catch (\Exception $e) {
            $response = oq_api_notify_error('Query Error', $e->getMessage(), 404);

            return ['success' => false, 'response' => $response];
        }
    
    }

    public function checkOrCreateNewBranch($company)
    {
        $branches = $company->branches()->count();

        if(!$branches){
            //  Create a new branch and return a fresh record
            $branch = $company->branches()->create([
                'name' => request('name') . ' - Branch 1',
            ])->fresh();

            return $branch; 
        }

    }

    public function checkAndAssignAccountToAuth($company)
    {
        $auth_user = auth('api')->user();

        /*  auth_assign:
         *  This is a variable used to determine if we should use the current company
         *  as the users company. Sometimes we want to assign the company being created to
         *  the current authenticated user. We can do this if the auth_assign variable has
         *  been set to a value equal to 1. This will update the users details and list the
         *  company as the one that the authenticated user belongs to.
         */
        $auth_assign = request('auth_assign');

        if (isset($auth_assign) && $auth_assign == 1) {
            //  Asssign the company and branch to the auth user
            User::find($auth_user->id)->update([
                'company_id' => $company->id,
                'company_branch_id' => $branch->id,
            ]);
        }
    }

    public function checkAndCreateRelationship($company)
    {
        $auth_user = auth('api')->user();

        /*  relationship:
         *  This is a variable used to determine if the current company being created has 
         *  a relationship as a customer/supplier to the auth users main company. Sometimes
         *  when creating a new company, we may want to assign that company as either a 
         *  customer/supplier to the company directory. We can do this if the relationship
         *  variable has been set with the appropriate type (customer/supplier)
         */
        $relationship = request('relationship') ?? null;

        if (isset($relationship) && !empty($relationship)) {
            
            //  Delete any previous relationship
            DB::table('company_directory')->where([
                ['company_id', $company->id],                           //  id of the current company
                ['owning_company_id', $auth_user->company_id]           //  id of the owning company
            ])->delete();

            //  Add to company directory
            DB::table('company_directory')->insert([
                'company_id' => $company->id,                           //  id of the current company
                'owning_branch_id' => $auth_user->company_branch_id,    //  id of the owning company branch
                'owning_company_id' => $auth_user->company_id,          //  id of the owning company 
                'type' => request('relationship'),                      //  relationship type e.g customer/supplier
                'created_at' => DB::raw('now()'),                       
                'updated_at' => DB::raw('now()')
            ]);
        }
    }

    public function checkAndUploadLogo($company)
    {
        /*  logo:
         *  This is a variable used to determine if the current company being created has
         *  a logo file to upload. Sometimes when creating a new company, we may want to 
         *  also upload the logo at the same time. We can do this if the logo variable
         *  has been set with the image file (type=binary)
         */
        $File = request('logo');

        if (isset($File) && !empty($File) && request()->hasFile('logo')) {

            //  Start upload process of files
            $data = ( new Document() )->saveDocument( request(), $company->id, 'company', $File, 'company_logos', 'logo', true );

        }
    }

    public function checkAndCreateSmsCredit($company)
    {
        //  Check if the company has an sms credit record
        $smsCredit = $company->smsCredits()->count();

        if (!$smsCredit) {
            //  Add sms credits to the new company
            $smsCredits = $company->smsCredits()->create([
                'count' => (new Sms())->defaultCredit,
            ]);
        }
    }

    public function checkAndCreateSettings($company)
    {
        //  Check if the company has any settings
        $settings = $company->settings()->count();

        if (!$settings) {
            //  Create new settings for the company
            $settings = $company->settings()->create([
                'details' => $this->settingsTemplate($company),
            ]);
        }
    }

    public function checkAndUpdatePhones($company)
    {
        /*  phones:
         *  This is a variable used to determine if the current company being created has 
         *  any phones to be added. Sometimes when creating a new company, we may want to 
         *  add and replace existing phones to that company. We can do this if the phones
         *  variable has been set with an array list of phone numbers.
         */
        //  Get any associated phones if any
        $phones = request('phones');
        $phones = is_array( $phones ) ? $phones : json_decode( $phones, true);
        
        if (isset($phones) && !empty($phones)) {
            //  Add new phone numbers
            $phoneInstance = new Phone();

            $data = $phoneInstance->initiateCreate($company->id, 'company', $phones, $replace=true);
            $success = $data['success'];
            $phones = $data['response'];
            
            if($success){
                return $phones;
            }
        }

        //  Phones not added
        return false;
    }

    public function settingsTemplate($company)
    {
        return [
            //  Get the general settings
            'general' => [
                //  Get the currency mathing the company country
                'currency_type' => $this->predictCurrency($company)
            ],

            //  Get settings for creating quotations
            'quotationTemplate' => (new Quotation())->sampleTemplate(),

            //  Get settings for creating invoices
            'invoiceTemplate' => (new Invoice())->sampleTemplate(),
            
        ];
    }

    public function predictCurrency($company)
    {
        //  Get all existing currencies
        $currencies = $this->getCurrencies();

        //  Variable to hold the selected currency matching the company country
        $selectedCurrency = null;

        //  Foreach currency, check if it matches the current company country
        foreach($currencies as $currency){
            //  If it matches the company country
            if($currency['country'] == $company->country){
                //  Select the currency
                $selectedCurrency = $currency;
            }
        }

        //  Return selected currency
        return $selectedCurrency;
    }

    /*  initiateUpdate() method:
     *
     *  This is used to update an existing company. It also works
     *  to store the update activity and broadcasting of
     *  notifications to users concerning the update of
     *  the company.
     *
     */
    public function initiateUpdate($company_id)
    {
        //  Current authenticated user
        $auth_user = auth('api')->user();

        /*******************************************************
         *   CHECK IF USER HAS PERMISSION TO UPDATE A COMPANY  *
         ******************************************************/

        /*********************************************
         *   VALIDATE COMPANY INFORMATION            *
         ********************************************/
         
        $template = [
            'name' => request('name'),
            'abbreviation' => request('abbreviation') ?? null,
            'description' => request('description') ?? null,
            'date_of_incorporation' => request('date_of_incorporation') ?? null,
            'type' => request('type') ?? null,
            'industry' => request('industry') ?? null,
            'address' => request('address') ?? null,
            'country' => request('country') ?? null,
            'province' => request('province') ?? null,
            'city' => request('city') ?? null,
            'postal_or_zipcode' => request('postal_or_zipcode') ?? null,
            'email' => request('email') ?? null,
            'additional_email' => request('additional_email') ?? null,
            'website_link' => request('website_link') ?? null,
            'facebook_link' => request('facebook_link') ?? null,
            'twitter_link' => request('twitter_link') ?? null,
            'linkedin_link' => request('linkedin_link') ?? null,
            'instagram_link' => request('instagram_link') ?? null,
            'bio' => request('bio') ?? null,
        ];

        try {
            //  Update the company
            $company = $this->where('id', $company_id)->first()->update($template);

            //  If the company was updated successfully
            if ($company) {
                //  re-retrieve the instance to get all of the fields in the table.
                $company = $this->where('id', $company_id)->first();

                //  Check whether or not this company has a branch otherwise create a new one
                $this->checkOrCreateNewBranch($company);

                //  Check whether or not to update the auth user as belonging to this company
                $this->checkAndAssignAccountToAuth($company);

                //  Check whether or not the auth company has a relationship with the created company e.g) customer/supplier
                $this->checkAndCreateRelationship($company);

                //  Check whether or not the company has any logo to upload
                $this->checkAndUploadLogo($company);

                //  Check if the company has an sms credit record otherwise create a new credit record
                $this->checkAndCreateSmsCredit($company);

                //  Check if the company has any settings otherwise create new settings
                $this->checkAndCreateSettings($company);

                //  Check if the company has any phones to add and replace
                $this->checkAndUpdatePhones($company);

                //  Refresh company
                $company = $company->fresh();

                /*****************************
                 *   SEND NOTIFICATIONS      *
                 *****************************/

                // $auth_user->notify(new AccountUpdated($company));

                /*****************************
                 *   RECORD ACTIVITY         *
                 *****************************/

                //  Record activity of company updated
                $status = 'updated';
                $companyUpdatedActivity = oq_saveActivity($company, $auth_user, $status, ['company' => $company->summarize()]);

                //  Action was executed successfully
                return ['success' => true, 'response' => $company];

            } else {
                //  No resource found
                return ['success' => false, 'response' => oq_api_notify_no_resource()];
            }
        } catch (\Exception $e) {
            //  Log the error
            $response = oq_api_notify_error('Query Error', $e->getMessage(), 404);

            //  Return the error response
            return ['success' => false, 'response' => $response];
        }
    }

    /*  initiateApprove() method:
     *
     *  This is used to approve an existing company. It also works
     *  to store the update activity and broadcasting of
     *  notifications to users concerning the approval of
     *  the company.
     *
     */
    public function initiateApprove($company_id)
    {
        //  Current authenticated user
        $auth_user = auth('api')->user();

        /*******************************************************
         *   CHECK IF USER HAS PERMISSION TO APPROVE QUOTATION   *
         ******************************************************/

        try {
            //  Get the company
            $company = $this->where('id', $company_id)->first();

            //  Check if we have an company
            if ($company) {
                /*****************************
                 *   SEND NOTIFICATIONS      *
                 *****************************/

                //  $auth_user->notify(new AccountApproved($company));

                /*****************************
                 *   RECORD ACTIVITY         *
                 *****************************/

                //  Record activity of company approved
                $status = 'approved';
                $companyApprovedActivity = oq_saveActivity($company, $auth_user, $status, ['company' => $company->summarize()]);

                //  Action was executed successfully
                return ['success' => true, 'response' => $company];
            } else {
                //  No resource found
                return ['success' => false, 'response' => oq_api_notify_no_resource()];
            }
        } catch (\Exception $e) {
            //  Log the error
            $response = oq_api_notify_error('Query Error', $e->getMessage(), 404);

            //  Return the error response
            return ['success' => false, 'response' => $response];
        }
    }

    /*  getStatistics() method:
     *
    /*  This method is used to get the overall statistics of the companies,
     *  showing information of companies in their respective states such as
     *  1) Name of status
     *  2) Total number of companies in each respective status
     *  3) Total sum of the grand totals in each respective status
     *  4) The base currency used by the associated company
     *
     *  Example of returned output:
        {
            "stats": [
                {
                    "grand_total": null,
                    "total_count": 0,
                    "name": "Draft"
                },
                {
                    "grand_total": 23450,
                    "total_count": 6,
                    "name": "Approved"
                },
                {
                    "grand_total": 45240,
                    "total_count": 2,
                    "name": "Sent"
                },
                {
                    "grand_total": 1250,
                    "total_count": 1,
                    "name": "Cancelled"
                },
                {
                    "grand_total": 18560,
                    "total_count": 5,
                    "name": "Expired"
                },
                {
                    "grand_total": 75880,
                    "total_count": 12,
                    "name": "Paid"
                }
            ],
            "base_currency": {
                "country": "Botswana",
                "currency": {
                    "iso": {
                        "code": "BWP",
                        "number": "072"
                    },
                    "name": "Pula",
                    "symbol": "P"
                }
            }
        }
     *
     */

    public function getStatistics()
    {
        //  Current authenticated user
        $auth_user = auth('api')->user();

        //  Start getting the companies
        $data = $this->initiateGetAll(['paginate' => false]);
        $success = $data['success'];
        $response = $data['response'];

        if ($success) {
            try {
                //  Get all the available companies so far
                $companies = $data['response'];

                //  From the list of companies we will group them by their directory_type e.g) customer, supplier, e.t.c
                //  After this we will map through each group (customer, supplier, e.t.c) and get the status name, total sum of
                //  the grand totals as well as the total count of grouped companies of that activity.
                /*
                 *  Example of returned output:
                 *
                    {
                        "Paid": {
                            "name": "Paid",
                            "grand_total": 44520,
                            "total_count": 5
                        },
                        "Sent": {
                            "name": "Sent",
                            "grand_total": 14000,
                            "total_count": 1
                        }
                    }
                 *
                 */

                $availableStats = collect($companies)->groupBy('directory_type')->map(function ($companyGroup, $key) {
                    return [
                        'name' => ucwords($key),                                //  e.g) Client, Supplier, e.t.c
                        'total_count' => collect($companyGroup)->count(),       //  12
                    ];
                });

                //  This is a list of all the statistics we want returned in their respective order
                $expectedStats = ['Supplier', 'Client'];

                //  From the list of expected stats, we will map through and inspect if the expected stat
                //  exists in the available stats we have collected. If it does then return back the existing
                //  stat, otherwise we will create a new array that will hold the expected stat name that does
                //  not exist, as well as put a grand total sum of zero and a total count of zero
                /*
                 *  Example of returned output:
                 *
                    [
                        {
                            "name": "Draft",
                            "grand_total": 0,
                            "total_count": 0
                        },
                        {
                            "name": "Approved",
                            "grand_total": 0,
                            "total_count": 0
                        },
                        {
                            "name": "Sent",
                            "grand_total": 14000,
                            "total_count": 1
                        },
                        {
                            "name": "Cancelled",
                            "grand_total": 0,
                            "total_count": 0
                        },
                        {
                            "name": "Expired",
                            "grand_total": 0,
                            "total_count": 0
                        },
                        {
                            "name": "Paid",
                            "grand_total": 44520,
                            "total_count": 5
                        }
                    ]
                 *
                 */
                $stats = collect($expectedStats)->map(function ($stat_name) use ($availableStats) {
                    if (collect($availableStats)->has(strtolower($stat_name))) {
                        return $availableStats[strtolower($stat_name)];
                    } else {
                        return [
                                    'name' => $stat_name,         //  e.g) Supplier, Customer
                                    'total_count' => 0,
                                ];
                    }
                });

                //  Calculate the overall stats e.g) Total Count
                $totalCount = ['name' => 'Total Accounts', 'total_count' => 0];

                foreach ($stats as $stat) {
                    $totalCount['total_count'] += $stat['total_count'];
                }

                //  Merge the overview stats, stats and base currency into one collection
                $data = [
                        'overview_stats' => [$totalCount],
                        'stats' => $stats,
                    ];

                //  Action was executed successfully
                return ['success' => true, 'response' => $data];
            } catch (\Exception $e) {
                //  Log the error
                $response = oq_api_notify_error('Query Error', $e->getMessage(), 404);

                //  Return the error response
                return ['success' => false, 'response' => $response];
            }
        } else {
            return ['success' => false, 'response' => $response];
        }
    }

    public function getBasicDetails()
    {
        //  Filter the collection to only the following details
        return $this->only([

                /*  Logo  */
                'logo',

                /*  Basic Info  */
                'name', 'abbreviation', 'description', 'date_of_incorporation', 'type', 'industry',
                
                /*  Address Info  */
                'address_1', 'address_2', 'country', 'province', 'city', 'postal_or_zipcode', 
                
                /*  Contact Info  */
                'email', 'additional_email', 'phones'
                
            ]);
    }
}
