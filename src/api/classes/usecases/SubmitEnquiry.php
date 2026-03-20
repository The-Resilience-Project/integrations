<?php

class SubmitEnquiry
{
    private ?VtApiClient $api;

    public function __construct(?VtApiClient $api = null)
    {
        $this->api = $api;
    }

    public function execute(EnquiryRequest $request): EnquiryResult
    {
        $organisation = $request->organisationDisplayName();
        $contactEmail = $request->contact->email;

        log_info('Enquiry submission started', [
            'service_type' => $request->serviceType,
            'organisation' => $organisation,
            'contact_email' => $contactEmail,
        ]);

        try {
            $controllerClass = $request->controllerClass();
            $controller = new $controllerClass($request->toArray(), $this->api);

            $success = $controller->submit_enquiry();

            if ($success) {
                log_info('Enquiry submitted successfully', [
                    'service_type' => $request->serviceType,
                    'organisation' => $organisation,
                ]);
            } else {
                log_error('Enquiry submission failed', [
                    'service_type' => $request->serviceType,
                    'organisation' => $organisation,
                ]);
            }

            return new EnquiryResult(
                success: $success,
                serviceType: $request->serviceType,
                organisation: $organisation,
                contactEmail: $contactEmail,
            );
        } catch (Exception $e) {
            log_exception($e, [
                'use_case' => 'SubmitEnquiry',
                'service_type' => $request->serviceType,
                'organisation' => $organisation,
            ]);

            return new EnquiryResult(
                success: false,
                serviceType: $request->serviceType,
                organisation: $organisation,
                contactEmail: $contactEmail,
                errorMessage: $e->getMessage(),
            );
        }
    }
}
