<?php

declare(strict_types=1);

namespace ApiV2\Application\Schools;

use ApiV2\Application\CustomerService;
use ApiV2\Domain\ConfirmationRequest;
use ApiV2\Domain\Schools\AssigneeRules;
use ApiV2\Domain\Schools\Deal;
use ApiV2\Infrastructure\VtigerWebhookClientInterface;

class SubmitConfirmationHandler
{
    private const SEIP_NAME = '2027 SEIP';
    private const QUOTE_TYPE = 'School - New';
    private const QUOTE_PROGRAM = 'School';
    private const QUOTE_STAGE = 'Delivered';
    private const TAX_RATE = 0.1;
    private const ENGAGE_CODE = 'SER12';
    private const INSPIRE_STANDARD = 'SER157';
    private const INSPIRE_SMALL_101_200 = 'SER158';
    private const INSPIRE_SMALL_0_100 = 'SER159';

    private VtigerWebhookClientInterface $client;

    public function __construct(VtigerWebhookClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Handle a school confirmation submission.
     *
     * @param ConfirmationRequest $request The validated confirmation request
     */
    public function handle(ConfirmationRequest $request): bool
    {
        $sourceForm = $request->sourceForm ?? 'New Schools Confirmation 2027';
        $contact = $request->toContact();
        $organisation = $request->toOrganisation();
        $participatingStudents = $request->participatingNumOfStudents ?? 0;

        // Step 1: Capture and update main customer
        $customerService = new CustomerService($this->client);
        log_info('Capturing and updating customer');
        $result = $customerService->captureAndUpdateCustomer($contact, $organisation, $sourceForm, $request->state);
        $captured = $result->captured;
        $orgDetails = $result->orgDetails;

        // Step 2: Create/get deal with "Deal Won"
        $deal = Deal::forSchoolConfirmation();
        $dealPayload = [
            'dealName' => $deal->name,
            'dealType' => $deal->type,
            'dealOrgType' => $deal->orgType,
            'dealStage' => $deal->stage,
            'dealCloseDate' => $deal->closeDate,
            'dealPipeline' => $deal->pipeline,
            'contactId' => $captured->contactId,
            'organisationId' => $captured->organisationId,
            'assignee' => AssigneeRules::resolveContactAssignee($orgDetails->assignedUserId, $request->state),
        ];
        if ($participatingStudents > 0) {
            $dealPayload['dealNumOfParticipants'] = $participatingStudents;
        }
        $dealPayload['dealState'] = $request->state;

        log_info('Step 2: Creating/getting deal', $dealPayload);
        $dealResponse = $this->client->post('getOrCreateDeal', $dealPayload);
        $dealData = $dealResponse->result[0];
        $dealId = $dealData->id;
        log_info('Step 2 complete: Deal retrieved', ['dealId' => $dealId]);

        // Step 3: Capture billing contact (if different)
        $billingContactId = null;
        $billingContactEmail = null;
        if ($request->differentBillingContact) {
            log_info('Step 3: Capturing billing contact', [
                'billingEmail' => $request->billingContactEmail,
            ]);
            $billingPayload = [
                'contactEmail' => $request->billingContactEmail,
                'contactFirstName' => $request->billingContactFirstName,
                'contactLastName' => $request->billingContactLastName,
                'contactType' => 'Billing',
                'organisationType' => 'School',
            ];
            if ($request->billingContactPhone !== null) {
                $billingPayload['contactPhone'] = $request->billingContactPhone;
            }
            if ($organisation->accountNo !== null) {
                $billingPayload['organisationAccountNo'] = $organisation->accountNo;
                $billingResponse = $this->client->post('captureCustomerInfoWithAccountNo', $billingPayload);
            } else {
                $billingPayload['organisationName'] = $organisation->name ?? '';
                $billingResponse = $this->client->post('captureCustomerInfo', $billingPayload);
            }
            $billingContactId = $billingResponse->result[0]->id;
            $billingContactEmail = $request->billingContactEmail;
            log_info('Step 3 complete: Billing contact captured', ['billingContactId' => $billingContactId]);
        } else {
            log_info('Step 3: No different billing contact, skipping');
        }

        // Step 4: Get line items
        log_info('Step 4: Getting line items');
        $lineItems = $this->getLineItems($request);
        log_info('Step 4 complete: Line items retrieved', ['count' => count($lineItems)]);

        // Step 5: Calculate total
        $total = 0;
        foreach ($lineItems as $item) {
            $total += $item['listprice'] * $item['quantity'];
        }
        log_info('Step 5: Total calculated', ['total' => $total]);

        // Step 6: Update deal with confirmation details
        $updateDealPayload = [
            'dealId' => $dealId,
            'contactId' => $captured->contactId,
            'dealStage' => 'Deal Won',
            'total' => $total,
            'address' => $request->address,
            'suburb' => $request->suburb,
            'postcode' => $request->postcode,
            'state' => $request->state,
        ];
        if ($billingContactId !== null) {
            $updateDealPayload['billingContactId'] = $billingContactId;
        }
        $engage = $request->engage ?? 'Journals';
        $updateDealPayload['engage'] = $engage;
        $inspire = $request->inspire ?? 'Inspire 1';
        $updateDealPayload['inspire'] = [$inspire];
        if ($request->mentalHealthFunding !== null) {
            $updateDealPayload['mentalHealthFunding'] = $request->mentalHealthFunding;
        }
        if ($request->selectedYearLevels !== null) {
            $updateDealPayload['selectedYearLevels'] = $request->selectedYearLevels;
        }

        log_info('Step 6: Updating deal with confirmation details', ['dealId' => $dealId]);
        $this->client->post('updateDeal', $updateDealPayload);

        // Step 7: Set deal line items (form-encoded)
        log_info('Step 7: Setting deal line items', ['dealId' => $dealId]);
        $this->client->postWithLineItems('setDealLineItems', [
            'dealId' => $dealId,
            'total' => $total,
        ], $lineItems);

        // Step 8: Create quote (form-encoded)
        $preTaxTotal = $total;
        $taxTotal = $total * self::TAX_RATE;
        $grandTotal = $total + $taxTotal;

        $quotePayload = [
            'dealId' => $dealId,
            'subject' => Deal::SCHOOL_DEAL_NAME,
            'type' => self::QUOTE_TYPE,
            'program' => self::QUOTE_PROGRAM,
            'stage' => self::QUOTE_STAGE,
            'contactId' => $captured->contactId,
            'contactEmail' => $request->contactEmail,
            'organisationId' => $captured->organisationId,
            'assignee' => AssigneeRules::resolveContactAssignee($orgDetails->assignedUserId, $request->state),
            'address' => $request->address,
            'suburb' => $request->suburb,
            'postcode' => $request->postcode,
            'state' => $request->state,
            'preTaxTotal' => $preTaxTotal,
            'grandTotal' => $grandTotal,
            'taxTotal' => $taxTotal,
        ];
        if ($billingContactId !== null) {
            $quotePayload['billingContactId'] = $billingContactId;
        }
        if ($billingContactEmail !== null) {
            $quotePayload['billingContactEmail'] = $billingContactEmail;
        }

        log_info('Step 8: Creating quote', ['dealId' => $dealId]);
        $quoteResponse = $this->client->postWithLineItems('createQuote', $quotePayload, $lineItems);
        $quoteId = $quoteResponse->result->id;
        log_info('Step 8 complete: Quote created', ['quoteId' => $quoteId]);

        // Step 9: Update organisation with years with TRP + address
        $yearsArray = array_filter(explode(' |##| ', $orgDetails->yearsWithTrp), fn ($v) => $v !== '');
        if (!in_array('2027', $yearsArray, true)) {
            $yearsArray[] = '2027';
        }
        log_info('Step 9: Updating organisation with years with TRP and address', [
            'organisationId' => $captured->organisationId,
            'yearsWithTrp' => $yearsArray,
        ]);
        $this->client->post('updateOrganisation', [
            'organisationId' => $captured->organisationId,
            'yearsWithTrp' => array_values($yearsArray),
            'address' => $request->address,
            'suburb' => $request->suburb,
            'postcode' => $request->postcode,
            'state' => $request->state,
        ]);

        // Step 10: Create SEIP
        log_info('Step 10: Creating SEIP', [
            'organisationId' => $captured->organisationId,
            'participants' => $participatingStudents,
        ]);
        $seipResponse = $this->client->post('createOrUpdateSEIP', [
            'seipName' => self::SEIP_NAME,
            'organisationId' => $captured->organisationId,
            'dateConfirmed' => date('d/m/Y'),
            'assignee' => $orgDetails->assignedUserId,
            'participants' => $participatingStudents,
            'dealId' => $dealId,
            'yearsWithTrp' => '1st year',
        ]);
        $seipId = $seipResponse->result[0]->id;
        log_info('Step 10 complete: SEIP created', ['seipId' => $seipId]);

        // Step 11: Link SEIP to contact
        log_info('Step 11: Linking SEIP to contact', [
            'contactId' => $captured->contactId,
            'seipId' => $seipId,
        ]);
        $this->client->post('updateContactById', [
            'contactId' => $captured->contactId,
            'seipId' => $seipId,
        ]);

        log_info('All steps complete');

        return true;
    }

    /**
     * Determine line items based on the confirmation request.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getLineItems(ConfirmationRequest $request): array
    {
        $inspireCode = self::INSPIRE_STANDARD;
        $usingMhf = $request->mentalHealthFunding === 'Yes';
        $isSmallSchool = $request->numOfStudents !== null && $request->numOfStudents <= 200;

        if (!$usingMhf && $isSmallSchool) {
            if ($request->numOfStudents > 100) {
                $inspireCode = self::INSPIRE_SMALL_101_200;
            } else {
                $inspireCode = self::INSPIRE_SMALL_0_100;
            }
        }

        $serviceCodes = [$inspireCode, self::ENGAGE_CODE];
        $servicesResponse = $this->client->post('getServices', ['serviceCodes' => $serviceCodes]);
        $services = $servicesResponse->result;

        $participatingStudents = $request->participatingNumOfStudents ?? 0;
        $lineItems = [];

        foreach ($services as $service) {
            $qty = $service->service_no === self::ENGAGE_CODE ? $participatingStudents : 1;
            $lineItems[] = [
                'productid' => $service->id,
                'quantity' => $qty,
                'listprice' => $service->unit_price,
                'tax5' => '10',
                'cf_quotes_xerocode' => $service->cf_services_xerocode,
                'duration' => 1,
                'section_name' => 'Display on Invoice',
                'section_no' => 1,
            ];
        }

        return $lineItems;
    }
}
