<?php

namespace App\Traits;

use DB;
use App\Document;

//  Notifications
use App\Store;
use App\Company;
use App\CompanyBranch;
use App\Notifications\OrderCreated;
use App\Notifications\OrderUpdated;

trait OrderTraits
{

    /*  initiateGetAll() method:
     *
     *  This is used to return a pagination of order results.
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

        //  If we overide using the request
        $requestPagination = request('paginate');
        if (isset($requestPagination) && ($requestPagination == 0 || $requestPagination == 1)) {
            $config['paginate'] = $requestPagination == 1 ? true : false;
        }

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
        $allocation = request('allocation');

        /*
         *  $orderStatus = 
        /*
         *  The $orderStatus variable is used to determine which status of orders to pull.
         *  It represents orders with a particular status. The user may request orders 
         *  with a status of:
         *  1) pending: Order received but unpaid
         *  2) processing: Order received and stock reduced (order awaiting fulfilment)
         *  3) on-hold: Order awaiting payment and confirmation
         *  4) completed: Order fulfiled (No further actions required)
         *  5) cancelled: Order was cancelled by admin/staff
         *  6) refunded: Order refunded by admin/staff
         *  7) failed : Order payment failed (via payment gateway)
         */
        $orderStatus = strtolower(request('orderStatus'));

        /*
         *  $storeId = 1, 2, 3, e.t.c
        /*
         *  The $storeId variable only get data specifically related to
         *  the specified store id. It is useful for scenerios where we
         *  want only orders of that store only
         */
        $storeId = request('storeId');

        /*
         *  $companyBranchId = 1, 2, 3, e.t.c
        /*
         *  The $companyBranchId variable only get data specifically related to
         *  the specified company branch id. It is useful for scenerios where we
         *  want only orders of that branch only
         */
        $companyBranchId = request('companyBranchId');

        /*
         *  $companyId = 1, 2, 3, e.t.c
        /*
         *  The $companyId variable only get data specifically related to
         *  the specified company id. It is useful for scenerios where we
         *  want only orders of that company only
         */
        $companyId = request('companyId');

        if( isset($storeId) && !empty($storeId) ){

            /********************************************************************
            *  CHECK IF THE USER IS AUTHORIZED TO GET SPECIFIED STORE ORDERS    *
            /********************************************************************/

            $model = Store::find($storeId);

        }else if( isset($companyBranchId) && !empty($companyBranchId) ){

            /********************************************************************
            *  CHECK IF THE USER IS AUTHORIZED TO GET SPECIFIED BRANCH ORDERS   *
            /********************************************************************/

            $model = CompanyBranch::find($companyBranchId);

        }else if( isset($companyId) && !empty($companyId) ){

            /**********************************************************************
            *  CHECK IF THE USER IS AUTHORIZED TO GET SPECIFIED COMPANY ORDERS    *
            /**********************************************************************/

            $model = Company::find($companyId);

        }else{

            //  Apply filter by allocation
            if ($allocation == 'all') {
                /***********************************************************
                *  CHECK IF THE USER IS AUTHORIZED TO ALL ORDERS         *
                /**********************************************************/

                //  Get the current order instance
                $model = $this;

            } elseif ($allocation == 'branch') {
                /*************************************************************
                *  CHECK IF THE USER IS AUTHORIZED TO GET BRANCH ORDERS    *
                /*************************************************************/

                // Only get orders associated to the company branch
                $model = $auth_user->companyBranch;
            } else {
                /**************************************************************
                *  CHECK IF THE USER IS AUTHORIZED TO GET COMPANY ORDERS    *
                /**************************************************************/

                //  Only get orders associated to the company
                $model = $auth_user->company;
            }

        }

        if(isset($orderStatus) && !empty( $orderStatus )){

            //  If the $orderStatus is a list e.g) pending,processing,on-hold ... e.t.c
            $orderStatus = explode(',', $orderStatus );

            //  If we have atleast one status
            if (count($orderStatus)) {
                //  Get orders only with the specified status
                $orders = $model->orders()->whereIn('status', $orderStatus);
            }

        } else {
            //  Otherwise get all orders
            $orders = $model->orders();
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

        $order_join = 'orders';

        try {
            //  Get all and trashed
            if (request('withtrashed') == 1) {
                //  Run query
                $orders = $orders->withTrashed()->advancedFilter(['order_join' => $order_join, 'paginate' => $config['paginate']]);
            //  Get only trashed
            } elseif (request('onlytrashed') == 1) {
                //  Run query
                $orders = $orders->onlyTrashed()->advancedFilter(['order_join' => $order_join, 'paginate' => $config['paginate']]);
            //  Get all except trashed
            } else {
                //  Run query
                $orders = $orders->advancedFilter(['order_join' => $order_join, 'paginate' => $config['paginate']]);
            }

            //  If we only want to know how many were returned
            if( request('count') == 1 ){
                //  If the orders are paginated
                if($config['paginate']){
                    $orders = $orders->total() ?? 0;
                //  If the orders are not paginated
                }else{
                    $orders = $orders->count() ?? 0;
                }
            }else{
                //  If we are not paginating then
                if (!$config['paginate']) {
                    //  Get the collection
                    $orders = $orders->get();
                }

                //  If we have any orders so far
                if ($orders) {
                    //  Eager load other relationships wanted if specified
                    if (strtolower(request('connections'))) {
                        $orders->load(oq_url_to_array(strtolower(request('connections'))));
                    }
                }
            }

            //  Action was executed successfully
            return ['success' => true, 'response' => $orders];

        } catch (\Exception $e) {
            //  Log the error
            $response = oq_api_notify_error('Query Error', $e->getMessage(), 404);

            //  Return the error response
            return ['success' => false, 'response' => $response];
        }
    }


    /*  initiateShow() method:
     *
     *  This is used to return only one specific order.
     *
     */
    public function initiateShow($order_id)
    {
        //  Current authenticated user
        $auth_user = auth('api')->user();

        try {
            //  Get the trashed order
            if (request('withtrashed') == 1) {
                //  Run query
                $order = $this->withTrashed()->where('id', $order_id)->first();

            //  Get the non-trashed order
            } else {
                //  Run query
                $order = $this->where('id', $order_id)->first();
            }

            //  If we have any order so far
            if ($order) {
                //  Eager load other relationships wanted if specified
                if (request('connections')) {
                    $order->load(oq_url_to_array(request('connections')));
                }

                //  Action was executed successfully
                return ['success' => true, 'response' => $order];
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

    /*  initiateCreate() method:
     *
     *  This is used to create a new order. It also works
     *  to order the creation activity and broadcasting of
     *  notifications to users concerning the creation of
     *  the order.
     *
     */
    public function initiateCreate($template = null)
    {
        //  Current authenticated user
        $auth_user = auth('api')->user();

        /*******************************************************
         *   CHECK IF USER HAS PERMISSION TO CREATE ORDER    *
         ******************************************************/

        /*********************************************
         *   VALIDATE ORDER INFORMATION            *
         ********************************************/
        
        //  Create a template to hold the order details
        $template = $template ?? [
            //  General details
            'title' => request('title'),
            'description' => request('description') ?? null,
            'type' => request('type') ?? null,
            
            //  Pricing details
            'cost_per_item' => request('cost_per_item') ?? 0,
            'price' => request('price') ?? 0,
            'sale_price' => request('sale_price') ?? 0,

            //  Inventory & Tracking details
            'sku' => request('sku') ?? null,
            'barcode' => request('barcode') ?? null,
            'quantity' => request('quantity') ?? null,
            'allow_inventory' => request('allow_inventory'),
            'auto_track_inventory' => request('auto_track_inventory'),
            
            //  Variant details
            'variants' => request('variants') ?? null,
            'variant_attributes' => request('variant_attributes') ?? null,
            'allow_variants' => request('allow_variants'),
            
            //  Download Details
            'allow_downloads' => request('allow_downloads'),

            //  Order Details
            'show_on_order' => request('show_on_order'),

            //  Ownership Details
            'company_branch_id' => $auth_user->company_branch_id ?? null,
            'company_id' => $auth_user->company_id ?? null,
        ];

        try {
            //  Create the order
            $order = $this->create($template)->fresh();

            //  If the order was created successfully
            if ($order) {

                //  Check whether or not the order has any image to upload
                $this->checkAndUploadImage($order);

                //  Check whether or not the order has any categories to add
                $this->checkAndCreateCategories($order);

                //  Check whether or not the order has any tags to add
                $this->checkAndCreateTags($order);

                //  refetch the updated order
                $order = $order->fresh();

                /*****************************
                 *   SEND NOTIFICATIONS      *
                 *****************************/

                // $auth_user->notify(new OrderCreated($order));

                /*****************************
                 *   RECORD ACTIVITY         *
                 *****************************/

                //  Record activity of order created
                $status = 'created';
                $orderCreatedActivity = oq_saveActivity($order, $auth_user, $status, ['order' => $order->summarize()]);

                //  Action was executed successfully
                return ['success' => true, 'response' => $order];
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

    /*  initiateUpdate() method:
     *
     *  This is used to update an existing order. It also works
     *  to order the update activity and broadcasting of
     *  notifications to users concerning the update of
     *  the order.
     *
     */
    public function initiateUpdate($order_id)
    {

        //  Current authenticated user
        $auth_user = auth('api')->user();

        /*******************************************************
         *   CHECK IF USER HAS PERMISSION TO UPDATE ORDER    *
         ******************************************************/

        /*********************************************
         *   VALIDATE ORDER INFORMATION            *
         ********************************************/

        //  Create a template to hold the order details
        $template = $template ?? [
            //  General details
            'title' => request('title'),
            'description' => request('description') ?? null,
            'type' => request('type') ?? null,
            
            //  Pricing details
            'cost_per_item' => request('cost_per_item') ?? 0,
            'price' => request('price') ?? 0,
            'sale_price' => request('sale_price') ?? 0,

            //  Inventory & Tracking details
            'sku' => request('sku') ?? null,
            'barcode' => request('barcode') ?? null,
            'quantity' => request('quantity') ?? null,
            'allow_inventory' => request('allow_inventory'),
            'auto_track_inventory' => request('auto_track_inventory'),
            
            //  Variant details
            'variants' => request('variants') ?? null,
            'variant_attributes' => request('variant_attributes') ?? null,
            'allow_variants' => request('allow_variants'),
            
            //  Download Details
            'allow_downloads' => request('allow_downloads'),

            //  Order Details
            'show_on_order' => request('show_on_order'),

            //  Ownership Details
            'company_branch_id' => $auth_user->company_branch_id ?? null,
            'company_id' => $auth_user->company_id ?? null,
        ];

        try {
            //  Update the order
            $order = $this->where('id', $order_id)->first()->update($template);

            //  If the order was updated successfully
            if ($order) {

                //  re-retrieve the instance to get all of the fields in the table.
                $order = $this->where('id', $order_id)->first();

                //  Check whether or not the order has any image to upload
                $this->checkAndUploadImage($order);

                //  Check whether or not the order has any categories to add
                $this->checkAndCreateCategories($order);

                //  Check whether or not the order has any tags to add
                $this->checkAndCreateTags($order);

                //  refetch the updated order
                $order = $order->fresh();

                /*****************************
                 *   SEND NOTIFICATIONS      *
                 *****************************/

                // $auth_user->notify(new OrderUpdated($order));

                /*****************************
                 *   RECORD ACTIVITY         *
                 *****************************/

                //  Record activity of order updated
                $status = 'updated';
                $orderUpdatedActivity = oq_saveActivity($order, $auth_user, $status, ['order' => $order->summarize()]);

                //  Action was executed successfully
                return ['success' => true, 'response' => $order];
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

    /*  summarize() method:
     *
     *  This is used to limit the information of the resource to very specific
     *  columns that can then be used for storage. We may only want to summarize
     *  the data to very important information, rather than storing everything along
     *  with useless information. In this instance we specify table columns
     *  that we want (we access the fillable columns of the model), while also
     *  removing any custom attributes we do not want to order
     *  (we access the appends columns of the model),
     */
    public function summarize()
    {
        //  Collect and select table columns
        return collect($this->fillable)
                //  Remove all custom attributes since the are all based on recent activities
                ->forget($this->appends);
    }
}
