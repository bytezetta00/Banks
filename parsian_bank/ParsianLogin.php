<?php

require_once "./global.php";

class ParsianLogin
{
    protected DOMDocument $domDocument;
    protected array $loginData;
    protected array $loginData2;
    public array $englishNumber;
    public function __construct(
        private string $userName = "6539486431",//"2741558191",//"0010517881",
        //"meysam8900",
        private string $password = "Amir@1362m@Ss",//"D@nyal0118DGk",//"M10510568m@Kk",
        //"SH@nyal0118DG",
        private string $account = "47001485069601",//"30101927557601",//"30101790267603",
        //"47001427876609",
        private string $proxy = PROXY,
        private string $proxyUserPwd = PROXYUSERPWD,
        public array $persianNumber = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
    ) {
        $this->domDocument = new DOMDocument();
        $this->loginData = $this->getLoginData();
        $this->loginData2 = $this->getLoginData2();
        $this->englishNumber = range(0, 9);
    }

    public function login()
    {
        $date = date('Y-m-d h:i:s.000', time());
        $previousDate = date('Y-m-d h:i:s.000', strtotime($date . ' -1 months'));
        //return $previousDate;
        $currentDate = str_replace(" ", "T", $date);
        $previousDate = str_replace(" ", "T", $previousDate);
        $fullCurrentDate = $currentDate . "Z";
        $fullPreviousDate = $previousDate . "Z";

        $firstPageUrl = "https://ipb.parsian-bank.ir/login.html";
        $firstPageResponse = $this->curlRequest($firstPageUrl);
        writeOnFile("responses/firstPageResponse.html", $firstPageResponse["body"]);

        $captchaUrl = "https://ipb.parsian-bank.ir/vendors/captcha/forLogin";
        $captchaResponse = $this->curlRequest($captchaUrl);
        writeOnFile('images/captcha.png', $captchaResponse["body"]);

        $this->loginData['captcha'] = readline('Enter the captcha:');
        var_dump($this->loginData);
        $loginUrl = 'https://ipb.parsian-bank.ir/login';
        $loginResponse = $this->curlRequest(
            $loginUrl,
            http_build_query($this->loginData)
            ,
            [
                "Accept:*/*",
                "Content-Type:application/x-www-form-urlencoded",
                //application/json
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
                "X-Requested-With:XMLHttpRequest",
            ]
        );
        // var_dump($loginResponse);die;
        writeOnFile('responses/loginResponse.html', $loginResponse["body"]);

        $this->loginData2['challengeKey'] = $loginResponse["body"];
        $this->loginData2['otpPassword'] = readline('Enter the SMS:');

        // $captcha2Response = $this->curlRequest($captchaUrl);
        // writeOnFile('images/captcha2.png', $captcha2Response["body"]);
        // $this->loginData2['captcha'] = readline('Enter the captcha2:');
        $this->loginData2['captcha'] = $this->loginData['captcha'];
        var_dump($this->loginData2);

        $login2Response = $this->curlRequest($loginUrl, http_build_query($this->loginData2));
        writeOnFile('responses/login2Response.html', $login2Response["body"]);
        var_dump(["login2Response",$login2Response["code"]]);
        //home page url 
        $homeUrl = "https://ipb.parsian-bank.ir/";
        $homeResponse = $this->curlRequest($homeUrl);
        writeOnFile('responses/homeResponse.html', $homeResponse["body"]);

        $randomForHash = $this->makeid(10);
        $versionUrl = "https://ipb.parsian-bank.ir/version.hash-v$randomForHash";//GKr5T8hr3q
        echo $versionUrl;
        // cdFzLiRuYR  //ziwmZfmIX7
        $versionResponse = $this->curlRequest($versionUrl
        ,[],[
            "Accept: */*", 
            "Content-Type:application/octet-stream",//;charset=UTF-8
            "Accept-Encoding:gzip, deflate, br",
            "Accept-Language:en-US,en;q=0.5",
            "Cache-Control:no-cache",
            "Connection:keep-alive",
            "Host:ipb.parsian-bank.ir",
            "Pragma:no-cache",
            "Referer:https://ipb.parsian-bank.ir/",
            "Sec-Fetch-Dest:empty",
            "Sec-Fetch-Mode:cors",
            "Sec-Fetch-Site:same-origin",
            // "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/117.0",
            "X-Requested-With:XMLHttpRequest",
        ]
    );
        writeOnFile('responses/versionResponse.html', $versionResponse["body"]);
        echo " --|   |-- ";
        echo base64_decode($versionResponse["body"]);
        echo " --|   |-- ".PHP_EOL;
        echo $versionResponse["body"];
        $currentVersion = $versionResponse["body"];
        $getAllPersianTranslationsUrl = "https://ipb.parsian-bank.ir/wb/translations/getAllPersianTranslations";
        $getAllPersianTranslationsResponse = $this->curlRequest($getAllPersianTranslationsUrl);
        writeOnFile('responses/getAllPersianTranslationsResponse.html', $getAllPersianTranslationsResponse["body"]);

        $translationsAppNameUrl = "https://ipb.parsian-bank.ir/translations/app.name/fa_IR";
        $translationsAppNameResponse = $this->curlRequest($translationsAppNameUrl);
        writeOnFile('responses/translationsAppNameResponse.html', $translationsAppNameResponse["body"]);

        $menu1Url = "https://ipb.parsian-bank.ir/app/conf/menu1.json";
        $menu1Response = $this->curlRequest($menu1Url);
        writeOnFile('responses/menu1Response.html', $menu1Response["body"]);

        $templateUrl = "https://ipb.parsian-bank.ir/app/templates/WbEmptyTopnavbar.html";
        $templateResponse = $this->curlRequest($templateUrl);
        writeOnFile('responses/templateResponse.html', $templateResponse["body"]);

        $wbSidebarUrl = "https://ipb.parsian-bank.ir/app/templates/WbSidebar.html";
        $wbSidebarResponse = $this->curlRequest($wbSidebarUrl);
        writeOnFile('responses/wbSidebarResponse.html', $wbSidebarResponse["body"]);
        
        $operationsUrl = "https://ipb.parsian-bank.ir/app/services/operations/DashboardOperationsService.ts-v$currentVersion";
        $operationsResponse = $this->curlRequest($operationsUrl);
        writeOnFile('responses/operationsResponse.html', $operationsResponse["body"]);

        $menu1Url = "https://ipb.parsian-bank.ir/app/conf/menu1.json";
        $menu1Response = $this->curlRequest($menu1Url);
        writeOnFile('responses/menu1Response.html', $menu1Response["body"]);
	
        $customMenuUrl = "https://ipb.parsian-bank.ir/app/templates/customMenu.html";
        $customMenuResponse = $this->curlRequest($customMenuUrl);
        writeOnFile('responses/customMenuResponse.html', $customMenuResponse["body"]);

        $getCurrentUserUrl = "https://ipb.parsian-bank.ir/customer/getCurrentUser";
        $getCurrentUserResponse = $this->curlRequest($getCurrentUserUrl);
        writeOnFile('responses/getCurrentUserResponse.html', $getCurrentUserResponse["body"]);

        $menu1Url = "https://ipb.parsian-bank.ir/app/conf/menu1.json";
        $menu1Response = $this->curlRequest($menu1Url);
        writeOnFile('responses/menu1Response.html', $menu1Response["body"]);

        $getSessionTimeoutValueUrl = "https://ipb.parsian-bank.ir/customer/getSessionTimeoutValue";
        $getSessionTimeoutResponse = $this->curlRequest($getSessionTimeoutValueUrl);
        writeOnFile('responses/getSessionTimeoutResponse.html', $getSessionTimeoutResponse["body"]);

        $dashboardControllerUrl = "https://ipb.parsian-bank.ir/app/controllers/DashboardController.ts-v$currentVersion";
        $dashboardControllerResponse = $this->curlRequest($dashboardControllerUrl);
        writeOnFile('responses/dashboardControllerResponse.html', $dashboardControllerResponse["body"]);

        $templatesWbInputUrl = "https://ipb.parsian-bank.ir/app/templates/WbInput.html";
        $templatesWbInputResponse = $this->curlRequest($templatesWbInputUrl);
        writeOnFile('responses/templatesWbInputResponse.html', $templatesWbInputResponse["body"]);

        $autoNormalTransferUrl = "https://ipb.parsian-bank.ir/app/services/operations/AutoNormalTransferReportOperationsService.ts-v$currentVersion";
        $autoNormalTransferResponse = $this->curlRequest($autoNormalTransferUrl);
        writeOnFile('responses/autoNormalTransferResponse.html', $autoNormalTransferResponse["body"]);

        $activeChequeBookletsUrl = "https://ipb.parsian-bank.ir/app/services/operations/ActiveChequeBookletsOperationsService.ts-v$currentVersion";
        $activeChequeBookletsResponse = $this->curlRequest($activeChequeBookletsUrl);
        writeOnFile('responses/activeChequeBookletsResponse.html', $activeChequeBookletsResponse["body"]);

        $normalFundTransferUrl = "https://ipb.parsian-bank.ir/app/services/operations/NormalFundTransferOperationsService.ts-v$currentVersion";
        $normalFundTransferResponse = $this->curlRequest($normalFundTransferUrl);
        writeOnFile('responses/normalFundTransferResponse.html', $normalFundTransferResponse["body"]);

        $statementOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/StatementOperationsService.ts-v$currentVersion";
        $statementOperationsServiceResponse = $this->curlRequest($statementOperationsServiceUrl);
        writeOnFile('responses/statementOperationsServiceResponse.html', $statementOperationsServiceResponse["body"]);

        $transferredOtherBankChequesUrl = "https://ipb.parsian-bank.ir/app/services/operations/TransferredOtherBankChequesStatusOperationsService.ts-v$currentVersion";
        $transferredOtherBankChequesResponse = $this->curlRequest($transferredOtherBankChequesUrl);
        writeOnFile('responses/transferredOtherBankChequesResponse.html', $transferredOtherBankChequesResponse["body"]);

        $cardToCardFundTransferOperationsUrl = "https://ipb.parsian-bank.ir/app/services/operations/CardToCardFundTransferOperationsService.ts-v$currentVersion";
        $cardToCardFundTransferOperationsResponse = $this->curlRequest($cardToCardFundTransferOperationsUrl);
        writeOnFile('responses/cardToCardFundTransferOperationsResponse.html', $cardToCardFundTransferOperationsResponse["body"]);

        $utilsTsUrl = "https://ipb.parsian-bank.ir/app/services/Utils.ts-v$currentVersion";
        $utilsTsResponse = $this->curlRequest($utilsTsUrl);
        writeOnFile('responses/utilsTsResponse.html', $utilsTsResponse["body"]);

        $chargeServiceOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/ChargeServiceOperationsService.ts-v$currentVersion";
        $chargeServiceOperationsServiceResponse = $this->curlRequest($chargeServiceOperationsServiceUrl);
        writeOnFile('responses/chargeServiceOperationsServiceResponse.html', $chargeServiceOperationsServiceResponse["body"]);

        $achFundTransferOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/AchFundTransferOperationsService.ts-v$currentVersion";
        $achFundTransferOperationsServiceResponse = $this->curlRequest($achFundTransferOperationsServiceUrl);
        writeOnFile('responses/achFundTransferOperationsServiceResponse.html', $achFundTransferOperationsServiceResponse["body"]);

        $rtgsFundTransferOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/RtgsFundTransferOperationsService.ts-v$currentVersion";
        $rtgsFundTransferOperationsServiceResponse = $this->curlRequest($rtgsFundTransferOperationsServiceUrl);
        writeOnFile('responses/rtgsFundTransferOperationsServiceResponse.html', $rtgsFundTransferOperationsServiceResponse["body"]);

        $charityOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/CharityOperationsService.ts-v$currentVersion";
        $charityOperationsServiceResponse = $this->curlRequest($charityOperationsServiceUrl);
        writeOnFile('responses/charityOperationsServiceResponse.html', $charityOperationsServiceResponse["body"]);

        $cardlessIssueVoucherOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/CardlessIssueVoucherOperationsService.ts-v$currentVersion";
        $cardlessIssueVoucherOperationsServiceResponse = $this->curlRequest($cardlessIssueVoucherOperationsServiceUrl);
        writeOnFile('responses/cardlessIssueVoucherOperationsServiceResponse.html', $cardlessIssueVoucherOperationsServiceResponse["body"]);

        $changeCardPasswordOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/ChangeCardPasswordOperationsService.ts-v$currentVersion";
        $changeCardPasswordOperationsServiceResponse = $this->curlRequest($changeCardPasswordOperationsServiceUrl);
        writeOnFile('responses/changeCardPasswordOperationsServiceResponse.html', $changeCardPasswordOperationsServiceResponse["body"]);

        $deactivationCardPasswordOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/DeactivationCardPasswordOperationsService.ts-v$currentVersion";
        $deactivationCardPasswordOperationsServiceResponse = $this->curlRequest($deactivationCardPasswordOperationsServiceUrl);
        writeOnFile('responses/deactivationCardPasswordOperationsServiceResponse.html', $deactivationCardPasswordOperationsServiceResponse["body"]);

        $blockSingularCardOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/BlockSingularCardOperationsService.ts-v$currentVersion";
        $blockSingularCardOperationsServiceResponse = $this->curlRequest($blockSingularCardOperationsServiceUrl);
        writeOnFile('responses/blockSingularCardOperationsServiceResponse.html', $blockSingularCardOperationsServiceResponse["body"]);

        $billPaymentOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/BillPaymentOperationsService.ts-v$currentVersion";
        $billPaymentOperationsServiceResponse = $this->curlRequest($billPaymentOperationsServiceUrl);
        writeOnFile('responses/billPaymentOperationsServiceResponse.html', $billPaymentOperationsServiceResponse["body"]);

        $changeLoginPasswordOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/ChangeLoginPasswordOperationsService.ts-v$currentVersion";
        $changeLoginPasswordOperationsServiceResponse = $this->curlRequest($changeLoginPasswordOperationsServiceUrl);
        writeOnFile('responses/changeLoginPasswordOperationsServiceResponse.html', $changeLoginPasswordOperationsServiceResponse["body"]);

        $dashboardUrl = "https://ipb.parsian-bank.ir/app/view/Dashboard.html";
        $dashboardResponse = $this->curlRequest($dashboardUrl);
        writeOnFile('responses/dashboardResponse.html', $dashboardResponse["body"]);

        $accountOptionsUrl = "https://ipb.parsian-bank.ir/account/options";
        $accountOptionsResponse = $this->curlRequest($accountOptionsUrl,[]);
        writeOnFile('responses/accountOptionsResponse.html', $accountOptionsResponse["body"]);

        $cardOptionsUrl = "https://ipb.parsian-bank.ir/card/options";
        $cardOptionsResponse = $this->curlRequest($cardOptionsUrl,[]);
        writeOnFile('responses/cardOptionsResponse.html', $cardOptionsResponse["body"]);

        $generalOptionsUrl = "https://ipb.parsian-bank.ir/general/options";
        $generalOptionsResponse = $this->curlRequest($generalOptionsUrl,[]);
        writeOnFile('responses/generalOptionsResponse.html', $generalOptionsResponse["body"]);

        $customerOptionsUrl = "https://ipb.parsian-bank.ir/customer/options";
        $customerOptionsResponse = $this->curlRequest($customerOptionsUrl,[]);
        writeOnFile('responses/customerOptionsResponse.html', $customerOptionsResponse["body"]);

        $chequeOptionsUrl = "https://ipb.parsian-bank.ir/cheque/options";
        $chequeOptionsResponse = $this->curlRequest($chequeOptionsUrl,[]);
        writeOnFile('responses/chequeOptionsResponse.html', $chequeOptionsResponse["body"]);

        $settingsOptionsUrl = "https://ipb.parsian-bank.ir/settings/options";
        $settingsOptionsResponse = $this->curlRequest($settingsOptionsUrl,[]);
        writeOnFile('responses/settingsOptionsResponse.html', $settingsOptionsResponse["body"]);

        $getCurrentUserUrl = "https://ipb.parsian-bank.ir/customer/getCurrentUser";
        $getCurrentUserResponse = $this->curlRequest($getCurrentUserUrl);
        writeOnFile('responses/getCurrentUserResponse.html', $getCurrentUserResponse["body"]);
        // var_dump($getCurrentUserResponse);
        
        $getSessionRemainTimeUrl = "https://ipb.parsian-bank.ir/customer/getSessionRemainTime";
        $getSessionRemainTimeResponse = $this->curlRequest($getSessionRemainTimeUrl);
        writeOnFile('responses/getSessionRemainTimeResponse.html', $getSessionRemainTimeResponse["body"]);
        
        $loadListOfRepresentativeCorporateUrl = "https://ipb.parsian-bank.ir/settings/loadListOfRepresentativeCorporate?contactInfoType=";
        $loadListOfRepresentativeCorporateResponse = $this->curlRequest($loadListOfRepresentativeCorporateUrl,[]);
        writeOnFile('responses/loadListOfRepresentativeCorporateResponse.html', $loadListOfRepresentativeCorporateResponse["body"]);

        $getUserDashboardUrl = "https://ipb.parsian-bank.ir/general/getUserDashboard";
        $getUserDashboardResponse = $this->curlRequest($getUserDashboardUrl);
        writeOnFile('responses/getUserDashboardResponse.html', $getUserDashboardResponse["body"]);
        
        $isLawyerRelatedUrl = "https://ipb.parsian-bank.ir/customer/isLawyerRelated?type=";
        $isLawyerRelatedResponse = $this->curlRequest($isLawyerRelatedUrl);
        writeOnFile('responses/isLawyerRelatedResponse.html', $isLawyerRelatedResponse["body"]);
        
        $isLawyerRelatedUrl = "https://ipb.parsian-bank.ir/cheque/getUpcomingDueDateCheques";
        $isLawyerRelatedResponse = $this->curlRequest($isLawyerRelatedUrl,[]);
        writeOnFile('responses/isLawyerRelatedResponse.html', $isLawyerRelatedResponse["body"]);
        
        $generalEchoUrl = "https://ipb.parsian-bank.ir/general/echo";
        $generalEchoResponse = $this->curlRequest($generalEchoUrl,[]);
        writeOnFile('responses/generalEchoResponse.html', $generalEchoResponse["body"]);

        $generalEchoUrl = "https://ipb.parsian-bank.ir/general/echo";
        $generalEchoResponse = $this->curlRequest($generalEchoUrl,[]);
        writeOnFile('responses/generalEchoResponse.html', $generalEchoResponse["body"]);

        $openTermAccountsOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/OpenTermAccountsOperationsService.ts-v$currentVersion";
        $openTermAccountsOperationsServiceResponse = $this->curlRequest($openTermAccountsOperationsServiceUrl);
        writeOnFile('responses/openTermAccountsOperationsServiceResponse.html', $openTermAccountsOperationsServiceResponse["body"]);

        $generalEchoUrl = "https://ipb.parsian-bank.ir/general/echo";
        $generalEchoResponse = $this->curlRequest($generalEchoUrl,[]);
        writeOnFile('responses/generalEchoResponse.html', $generalEchoResponse["body"]);

        $openTermAccountsControllerUrl = "https://ipb.parsian-bank.ir/app/controllers/OpenTermAccountsController.ts-v$currentVersion";
        $openTermAccountsControllerResponse = $this->curlRequest($openTermAccountsControllerUrl);
        writeOnFile('responses/openTermAccountsControllerResponse.html', $openTermAccountsControllerResponse["body"]);

        $fileServiceUrl = "https://ipb.parsian-bank.ir/app/services/FileService.ts-v$currentVersion";
        $fileServiceResponse = $this->curlRequest($fileServiceUrl);
        writeOnFile('responses/fileServiceResponse.html', $fileServiceResponse["body"]);

        $uIUtilsUrl = "https://ipb.parsian-bank.ir/app/services/UIUtils.ts-v$currentVersion";
        $uIUtilsResponse = $this->curlRequest($uIUtilsUrl);
        writeOnFile('responses/uIUtilsResponse.html', $uIUtilsResponse["body"]);

        $reportUtilsTsUrl = "https://ipb.parsian-bank.ir/app/services/ReportUtils.ts-v$currentVersion";
        $reportUtilsTsResponse = $this->curlRequest($reportUtilsTsUrl);
        writeOnFile('responses/reportUtilsTsResponse.html', $reportUtilsTsResponse["body"]);

        $achTransferReportOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/AchTransferReportOperationsService.ts-v$currentVersion";
        $achTransferReportOperationsServiceResponse = $this->curlRequest($achTransferReportOperationsServiceUrl);
        writeOnFile('responses/achTransferReportOperationsServiceResponse.html', $achTransferReportOperationsServiceResponse["body"]);

        $paymentByReferenceCodeOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/PaymentByReferenceCodeOperationsService.ts-v$currentVersion";
        $paymentByReferenceCodeOperationsServiceResponse = $this->curlRequest($paymentByReferenceCodeOperationsServiceUrl);
        writeOnFile('responses/paymentByReferenceCodeOperationsServiceResponse.html', $paymentByReferenceCodeOperationsServiceResponse["body"]);

        $accountBlockReasonsOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/AccountBlockReasonsOperationsService.ts-v$currentVersion";
        $accountBlockReasonsOperationsServiceResponse = $this->curlRequest($accountBlockReasonsOperationsServiceUrl);
        writeOnFile('responses/accountBlockReasonsOperationsServiceResponse.html', $accountBlockReasonsOperationsServiceResponse["body"]);

        $averageAccountBalanceOperationsServiceUrl = "https://ipb.parsian-bank.ir/app/services/operations/AverageAccountBalanceOperationsService.ts-v$currentVersion";
        $averageAccountBalanceOperationsServiceResponse = $this->curlRequest($averageAccountBalanceOperationsServiceUrl);
        writeOnFile('responses/averageAccountBalanceOperationsServiceResponse.html', $averageAccountBalanceOperationsServiceResponse["body"]);

        $openTermAccountsUrl = "https://ipb.parsian-bank.ir/app/view/account/OpenTermAccounts.html";
        $openTermAccountsResponse = $this->curlRequest($openTermAccountsUrl);
        writeOnFile('responses/openTermAccountsResponse.html', $openTermAccountsResponse["body"]);

        $optionsUrl = "https://ipb.parsian-bank.ir/account/options";
        $optionsResponse = $this->curlRequest($optionsUrl);
        writeOnFile('responses/optionsResponse.html', $optionsResponse["body"]);
        
        $accountGetAccountStatusesUrl = "https://ipb.parsian-bank.ir/account/getAccountStatuses";
        $accountGetAccountStatusesResponse = $this->curlRequest($accountGetAccountStatusesUrl);
        writeOnFile('responses/accountGetAccountStatusesResponse.html', $accountGetAccountStatusesResponse["body"]);
        
        $accountUnusedAccountsUrl = "https://ipb.parsian-bank.ir/account/unusedAccounts";
        $accountUnusedAccountsResponse = $this->curlRequest($accountUnusedAccountsUrl);
        writeOnFile('responses/accountUnusedAccountsResponse.html', $accountUnusedAccountsResponse["body"]);

        $wbCollapsiblePanelUrl = "https://ipb.parsian-bank.ir/app/templates/WbCollapsiblePanel.html";
        $wbCollapsiblePanelResponse = $this->curlRequest($wbCollapsiblePanelUrl);
        writeOnFile('responses/wbCollapsiblePanelResponse.html', $wbCollapsiblePanelResponse["body"]);

        $wbReportPrinterUrl = "https://ipb.parsian-bank.ir/app/templates/wbReportPrinter.html";
        $wbReportPrinterResponse = $this->curlRequest($wbReportPrinterUrl);
        writeOnFile('responses/wbReportPrinterResponse.html', $wbReportPrinterResponse["body"]);

        $getSessionRemainTimeUrl = "https://ipb.parsian-bank.ir/customer/getSessionRemainTime";
        $getSessionRemainTimeResponse = $this->curlRequest($getSessionRemainTimeUrl);
        writeOnFile('responses/getSessionRemainTimeResponse.html', $getSessionRemainTimeResponse["body"]);
        
        // $offlineStatementUrl = "https://ipb.parsian-bank.ir/app/services/operations/OfflineStatementNormalReportOperationsService.ts-v$currentVersion";
        // $offlineStatementResponse = $this->curlRequest($offlineStatementUrl);
        // writeOnFile('responses/offlineStatementResponse.html', $offlineStatementResponse["body"]);

        // $offlineStatementHtmlUrl = "https://ipb.parsian-bank.ir/app/view/offlinestatement/OfflineStatementNormalReport.html";
        // $offlineStatementHtmlResponse = $this->curlRequest($offlineStatementHtmlUrl);
        // writeOnFile('responses/offlineStatementHtmlResponse.html', $offlineStatementHtmlResponse["body"]);

        // $getOfflineStatementReportUrl = "https://ipb.parsian-bank.ir/report/getOfflineStatementReport";
        // $getOfflineStatementReportResponse = $this->curlRequest($getOfflineStatementReportUrl);
        // writeOnFile('responses/getOfflineStatementReportResponse.html', $getOfflineStatementReportResponse["body"]);

        // $openTermAccountsOperationsUrl = "https://ipb.parsian-bank.ir/app/services/operations/OpenTermAccountsOperationsService.ts-v$currentVersion";
        // $openTermAccountsOperationsResponse = $this->curlRequest($openTermAccountsOperationsUrl);
        // writeOnFile('responses/openTermAccountsOperationsResponse.html', $openTermAccountsOperationsResponse["body"]);

        // $openTermAccountsControllerUrl = "https://ipb.parsian-bank.ir/app/controllers/OpenTermAccountsController.ts-v$currentVersion";
        // $openTermAccountsControllerResponse = $this->curlRequest($openTermAccountsControllerUrl);
        // writeOnFile('responses/openTermAccountsControllerResponse.html', $openTermAccountsControllerResponse["body"]);

        // $uIUtilsUrl = "https://ipb.parsian-bank.ir/app/services/UIUtils.ts-v$currentVersion";
        // $uIUtilsResponse = $this->curlRequest($uIUtilsUrl);
        // writeOnFile('responses/uIUtilsResponse.html', $uIUtilsResponse["body"]);

        // $uIUtilsUrl = "https://ipb.parsian-bank.ir/app/services/UIUtils.ts-v$currentVersion";
        // $uIUtilsResponse = $this->curlRequest($uIUtilsUrl);
        // writeOnFile('responses/uIUtilsResponse.html', $uIUtilsResponse["body"]);

        // $openTermAccountsUrl = "https://ipb.parsian-bank.ir/app/view/account/OpenTermAccounts.html";
        // $openTermAccountsResponse = $this->curlRequest($openTermAccountsUrl);
        // writeOnFile('responses/openTermAccountsResponse.html', $openTermAccountsResponse["body"]);

        // $wbReportPrinterUrl = "https://ipb.parsian-bank.ir/app/templates/wbReportPrinter.html";
        // $wbReportPrinterResponse = $this->curlRequest($wbReportPrinterUrl);
        // writeOnFile('responses/wbReportPrinterResponse.html', $wbReportPrinterResponse["body"]);

        $getAccountSignatureOwnerStatusesUrl = "https://ipb.parsian-bank.ir/account/getAccountSignatureOwnerStatuses";
        $getAccountSignatureOwnerStatusesResponse = $this->curlRequest($getAccountSignatureOwnerStatusesUrl);
        writeOnFile('responses/getAccountSignatureOwnerStatusesResponse.html', $getAccountSignatureOwnerStatusesResponse["body"]);

        $getOpenTermAccountsData = [
            "ownerStatuses" => '',//["DEPOSIT_OWNER", "OWNER_OF_DEPOSIT_AND_SIGNATURE", "SIGNATURE_OWNER", "BROKER"],
            "accountStatus" => "OPEN",
            "alias" => ""
        ];
        $getOpenTermAccountsDataJson = json_encode($getOpenTermAccountsData);
        var_dump($getOpenTermAccountsDataJson);
        $getOpenTermAccountsUrl = "https://ipb.parsian-bank.ir/account/getOpenTermAccounts";
    //     $getOpenTermAccountsResponse = $this->curlRequest($getOpenTermAccountsUrl, $getOpenTermAccountsDataJson,
    //     [
    //         "Accept:*/*",
    //         "Content-Type:application/json",// application/x-www-form-urlencoded
    //         "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/116.0",
    //     ]
    // );
        // writeOnFile('responses/getOpenTermAccountsResponse.html', $getOpenTermAccountsResponse["body"]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_PROXY,PROXY);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD,PROXYUSERPWD);
        curl_setopt($ch, CURLOPT_URL, $getOpenTermAccountsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: */*", 
            "Content-Type: application/json",//;charset=utf-8
            // "browser-mode: public",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
            "X-KL-ksospc-Ajax-Request:Ajax_Request"
        ]);
        curl_setopt($ch, CURLOPT_COOKIEJAR, COOKIE_FILE);
        curl_setopt($ch, CURLOPT_COOKIEFILE, COOKIE_FILE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $getOpenTermAccountsDataJson);
        $firstResponse = curl_exec($ch); // output: "";
        writeOnFile('responses/getOpenTermAccountsResponse.html', $firstResponse);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        var_dump([$code,$content_type]);

        // {"ownerStatuses":["DEPOSIT_OWNER","OWNER_OF_DEPOSIT_AND_SIGNATURE","SIGNATURE_OWNER","BROKER"],"accountStatus":"OPEN","alias":""}

        // $statementHtmlUrl = "https://ipb.parsian-bank.ir/app/view/account/Statement.html";
        // $statementHtmlResponse = $this->curlRequest($statementHtmlUrl);
        // writeOnFile('responses/statementHtmlResponse.html', $statementHtmlResponse["body"]);

        // $settingsUrl = "https://ipb.parsian-bank.ir/settings/getStatementSettingsProfile?contactInfoType=";
        // $settingsResponse = $this->curlRequest($settingsUrl);
        // writeOnFile('responses/settingsResponse.html', $settingsResponse["body"]);

        // $statementRowModeUrl = "https://ipb.parsian-bank.ir/app/view/account/StatementRowMode.html";
        // $statementRowModeResponse = $this->curlRequest($statementRowModeUrl);
        // writeOnFile('responses/statementRowModeResponse.html', $statementRowModeResponse["body"]);

        // $statementCalendarModeUrl = "https://ipb.parsian-bank.ir/app/view/account/StatementCalendarMode.html";
        // $statementCalendarModeResponse = $this->curlRequest($statementCalendarModeUrl);
        // writeOnFile('responses/statementCalendarModeResponse.html', $statementCalendarModeResponse["body"]);

        // $statementTimelineModeUrl = "https://ipb.parsian-bank.ir/app/view/account/StatementTimelineMode.html";
        // $statementTimelineModeResponse = $this->curlRequest($statementTimelineModeUrl);
        // writeOnFile('responses/statementTimelineModeResponse.html', $statementTimelineModeResponse["body"]);

        // {"accountNumber":"47001427876609","orderType":2,"fromDate":1692477000294,"length":null}
        $accountStatementData = [
            "accountNumber" => $this->account,
            "orderType" => 2,
            "fromDate" => 1701462600295,
            "length" => null
        ];
        $accountStatementUrl = "https://ipb.parsian-bank.ir/account/statement";
        $accountStatementResponse = $this->curlRequest(
            $accountStatementUrl,
            json_encode($accountStatementData),
            [
                "Accept:*/*",
                "Content-Type:application/json",// application/x-www-form-urlencoded
                // "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
                // "Accept-Encoding:gzip, deflate, br",
                // "Accept-Language:en-US,en;q=0.5",
                // "browser-mode:public",
                "Cache-Control:no-cache",
                "Connection:keep-alive",
                // "Content-Length:219",
                "Host:ipb.parsian-bank.ir",
                "Origin:https://ipb.parsian-bank.ir",
                "Pragma:no-cache",
                "Referer:https://ipb.parsian-bank.ir/",
                "Sec-Fetch-Dest:empty",
                "Sec-Fetch-Mode:cors",
                "Sec-Fetch-Site:same-origin",
                "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/116.0",
                // "X-Requested-With:XMLHttpRequest",
                "X-KL-ksospc-Ajax-Request:Ajax_Request"
            ]
        );
        writeOnFile('responses/accountStatementResponse.html', $accountStatementResponse["body"]);
        var_dump($accountStatementResponse);

        $statementData = [
            "accountNumber" => $this->account,
            "fromDate" => "2023-11-03T20:00:00.000Z",
            "toDate" => "2023-12-03T07:25:58.827Z"
        ];
        // {"accountNumber":"47001427876609","orderType":2,"fromDate":1689796800000,"length":null,"fromDateTime":"","toDateTime":"","chequeSerial":"","receiptNo":"","description":"","customerDesc":"","fromAmount":"","toAmount":"","toDate":1692561600000}
        $statementUrl = "https://ipb.parsian-bank.ir/account/statement";
        $statementResponse = $this->curlRequest(
            $statementUrl,
            json_encode($statementData),
            [
                "Accept:*/*",
                "Content-Type:application/json",// application/x-www-form-urlencoded
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
            ]
        );
        writeOnFile('responses/statement.html', $statementResponse["body"]);

        $accountUrl = "https://ipb.parsian-bank.ir/account/getOpenTermAccounts";
        $accountData = [
            "ownerStatuses" => ["DEPOSIT_OWNER", "OWNER_OF_DEPOSIT_AND_SIGNATURE", "SIGNATURE_OWNER", "BROKER"],
            "accountStatus" => "OPEN"
        ];
        $accountResponse = $this->curlRequest(
            $accountUrl,
            json_encode($accountData),
            [
                "Accept:*/*",
                "Content-Type:application/json",
                // application/x-www-form-urlencoded
                "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
                // "X-Requested-With:XMLHttpRequest",
            ]
        );
        writeOnFile('responses/accountResponse.html', $accountResponse["body"]);

        //https://ipb.parsian-bank.ir/account/statement //for statement POST
        // {"accountNumber":"47001427876609","fromDate":"2023-07-22T20:00:00.000Z","toDate":"2023-08-15T07:25:58.827Z"} //json
        // https://ipb.parsian-bank.ir/account/getOpenTermAccounts //for balance POST 
        // {"ownerStatuses":["DEPOSIT_OWNER","OWNER_OF_DEPOSIT_AND_SIGNATURE","SIGNATURE_OWNER","BROKER"],"accountStatus":"OPEN"}
        return strlen($accountResponse["body"]);
    }

    public function curlRequest(string $url, $data = NULL, $headers = [], $proxy = PROXY, $proxyuserpwd = PROXYUSERPWD, $cookieFile = COOKIE_FILE, $userPass = null)
    {
        // echo "geting data from URL:$url";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // curl_setopt($ch, CURLOPT_TIMEOUT, 10); //timeout in seconds
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        // curl_setopt($ch, CURLOPT_ENCODING, 'identity');

        $resHeaders = [];
        // this function is called by curl for each header received
        curl_setopt(
            $ch,
            CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$resHeaders) {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                    return $len;

                $resHeaders[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );


        if ($userPass) {
            curl_setopt($ch, CURLOPT_USERPWD, $userPass);
        }

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if (!empty($proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }

        if (!empty($proxyuserpwd)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyuserpwd);
        }

        $response = curl_exec($ch);
        if (curl_error($ch)) {
            trigger_error('Curl Error:' . curl_error($ch));
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            "body" => $response,
            "headers" => $resHeaders,
            "code" => $code
        ];
    }

    public function getLoginData()
    {
        return [
            "challengeKey" => "",
            "langKey" => "fa",
            "browserMode" => "public",
            "otpInProgress" => "false",
            "currentStep" => "1",
            "pib_username" => $this->userName,
            "pib_password" => $this->password,
            "passwordType" => "S",
            "." => "",
        ];
    }

    public function getLoginData2()
    {
        return [
            "challengeKey" => "",
            //"7204495e-2db0-41a9-9579-d2ae4d6",
            "langKey" => "fa",
            "browserMode" => "public",
            "otpInProgress" => "true",
            "currentStep" => "2",
            "pib_username" => $this->userName,
            "pib_password" => $this->password,
            "passwordType" => "S",
            "otpPassword" => "",
            "captcha" => "bbxhc",
        ];
    }

    public function makeid(int $length) {
        $result = '';
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $charactersLength = strlen($characters);
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[rand(0, $charactersLength-1)];
        }
        return $result;
    }

}

$parsianLogin = new ParsianLogin();

var_dump($parsianLogin->login());