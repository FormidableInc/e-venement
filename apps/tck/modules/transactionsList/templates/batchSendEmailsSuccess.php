<?php use_helper('CrossAppLink') ?>
<?php use_javascript('jquery', 'first') ?>
<ul id="transactions_id" data-return-url="<?php echo url_for('transactionsList/index') ?>">
<?php foreach ( $transactions as $tid ): ?>
    <li><?php echo cross_app_link_to('transaction #'.$tid, 'pub', 'transaction/sendEmail?id='.$tid.'&token='.md5($tid.'|*|*|'.sfConfig::get('project_eticketting_salt', 'e-venement'))) ?></li>
<?php endforeach ?>
</ul>
<script type="text/javascript"><!--
    LI.pubSendEmail = function(index) {
        if ( index === undefined ) {
            index = 0;
        }
        
        var emailLink = $($('#transactions_id a')[index]);
        if ( emailLink.length == 0 ) {
            window.location = $('#transactions_id').attr('data-return-url');
            return;
        }
        
        $.get(emailLink.prop('href'), function(){
            LI.pubSendEmail(++index);
        });
    }
    $(document).ready(function(){
        LI.pubSendEmail();
        $('#transition').show();
    });
--></script>
