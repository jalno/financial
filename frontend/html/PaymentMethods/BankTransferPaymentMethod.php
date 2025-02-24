<?php

use packages\base\{http, Packages};
use packages\userpanel\Authentication;
use packages\userpanel\Date;
use themes\clipone\utility;
use packages\financial\transaction_pay;
use function packages\userpanel\url;

$parameter = array();
if ($token = http::getURIData("token")) {
    $parameter["token"] = $token;
}
$isLogin = Authentication::check();
$this->the_header(!$isLogin ? "logedout" : "");
?>
<div class="row">
    <div class="col-md-7">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-university"></i> <?php echo t("packages.financial.banks.accounts"); ?>
                <div class="panel-tools">
                    <a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
                </div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php echo t("packages.financial.banks.account.title"); ?></th>
                                <th><?php echo t("packages.financial.banks.account.account"); ?></th>
                                <th><?php echo t("packages.financial.banks.account.cart"); ?></th>
                                <th><?php echo t("packages.financial.banks.account.owner"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($this->bankAccounts as $account) {
                            ?>
                                <tr>
                                    <td><?php echo $account->bank->title; ?></td>
                                    <td><?php echo $account->account ? $account->account : "-"; ?><br><?php echo $account->shaba; ?></td>
                                    <td><?php echo $account->cart ? $account->cart : "-"; ?></td>
                                    <td><?php echo $account->owner; ?></td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
        $pays = $this->getExistsBanktransferPays();
        if ($pays) {
        ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-credit-card-alt"></i><?php echo t("packages.financial.pays.banktransfer"); ?>
                <div class="panel-tools">
                    <a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
                </div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><?php echo t("date&time"); ?></th>
                                <th><?php echo t("transaction.banktransfer.price"); ?></th>
                                <th><?php echo t("packages.financial.pays.banktransfer_to"); ?></th>
                                <th><?php echo t("description"); ?></th>
                                <th><?php echo t("pay.status"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($pays as $pay) {
                            $statusClass = utility::switchcase($pay->status, array(
                                'label label-danger' => transaction_pay::rejected,
                                'label label-success' => transaction_pay::accepted,
                                'label label-warning' => transaction_pay::pending
                            ));
                            $statusTxt = utility::switchcase($pay->status, array(
                                'pay.rejected' => transaction_pay::rejected,
                                'pay.accepted' => transaction_pay::accepted,
                                'pay.pending' => transaction_pay::pending
                            ));
                        ?>
                            <tr>
                                <td class="center ltr"><?php echo Date::format("Y/m/d H:i", $pay->date); ?></td>
                                <td><?php echo number_format($pay->price) . " " . $pay->currency->title ?></td>
                                <td>
                                <?php
                                $account = $this->getAccount($pay);
                                echo $account->cart;
                                ?>
                                </td>
                                <td>
                                <?php
                                echo t("pay.banktransfer.description-followup", ["followup" => $pay->param("followup")]);
                                $description = $pay->param("description");
                                if ($description) {
                                    echo "<br>" . $description;
                                }
                                $attachment = $pay->param("attachment");
                                if ($attachment) {
                                    $url = Packages::package("financial")->url($attachment);
                                    echo "<br><a href=\"{$url}\" target=\"_blank\"><i class=\"fa fa-paperclip\"></i>" . t("pay.banktransfer.attachment") . "</a>";
                                }
                                ?>
                                </td>
                                <td><span class="<?php echo $statusClass; ?>"><?php echo t($statusTxt); ?></span></td>
                            </tr>
                        <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php } ?>
    </div>
    <div class="col-md-5">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="clip-banknote"></i> <?php echo t('pay.byBankTransfer'); ?>
                <div class="panel-tools">
                    <a class="btn btn-xs btn-link panel-collapse collapses" href="#"></a>
                </div>
            </div>
            <div class="panel-body">
                <form action="<?php echo url('transactions/pay/banktransfer/' . $this->transaction->id, $parameter); ?>" method="POST" role="form" enctype="multipart/form-data" class="pay_banktransfer_form">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="well label-info">
                                <?php echo t("packages.financial.remain_price"); ?>:
                                <span class="pull-left"><?php echo number_format($this->remainPriceForAddPay) . " " . $this->transaction->currency->title; ?></span>
                            </div>
                        </div>
                        <div class="col-xs-12">
                            <?php
                            echo $this->createField(array(
                                'name' => 'price',
                                'label' => t("pay.banktransfer.price"),
                                'ltr' => true,
                                "input-group" => array(
                                    "right" => array(
                                        array(
                                            "type" => "addon",
                                            "text" => $this->transaction->currency->title,
                                        ),
                                    ),
                                ),
                            ));
                            ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            <?php
                            echo $this->createField(array(
                                'type' => 'select',
                                'name' => 'bankaccount',
                                'label' => t("pay.banktransfer.bankaccount"),
                                'options' => $this->getBankAccountsForSelect(),
                            ));
                            ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            <?php
                            echo $this->createField(array(
                                'name' => 'date',
                                'label' => t("pay.banktransfer.date"),
                                "ltr" => true,
                            ));
                            ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            <?php
                            $this->createField(array(
                                'name' => 'followup',
                                'label' => t("pay.banktransfer.followup"),
                                'type' => 'number',
                                'min' => 0,
                                "ltr" => true,
                            ));
                            $this->createField(array(
                                'name' => 'attachment',
                                'label' => t("pay.banktransfer.attachment"),
                                'type' => 'file',
                                "ltr" => true,
                            ));
                            $this->createField(array(
                                'name' => 'description',
                                'label' => t("description"),
                                'type' => 'textarea',
                                'class' => 'form-control banktransfer-description',
                                'rows' => 2
                            ));
                            ?>
                        </div>
                    </div>
                    <div class="row" style="margin-top: 20px;margin-bottom: 20px;">
                        <div class="col-md-4">
                            <a href="<?php echo url('transactions/view/' . $this->transaction->id); ?>" class="btn btn-block btn-default"><i class="fa fa-arrow-circle-right"></i> <?php echo t("return"); ?></a>
                        </div>
                        <div class="col-md-8">
                            <button class="btn btn-teal btn-block" type="submit"><i class="fa fa-arrow-circle-left"></i> <?php echo t('submit'); ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->the_footer(!$isLogin ? "logedout" : "");
