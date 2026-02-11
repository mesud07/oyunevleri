</div>
<!-- END MAIN CONTAINER -->

<!-- ###################### UP - DOWN ###################### -->
<div class="d-print-none" style="position: fixed; right: 0; top: 50%;" id="up_down">
    <a href="javascript:scrollToTop()"><img src="//cdn.kayhan.co/images/arrow_03_up.png"></a>
    <br> &nbsp; <br>
    <a href="javascript:scrollToBottom()"><img src="//cdn.kayhan.co/images/arrow_03_down.png"></a>
</div>
<!--div id="topup">OLD VERSION</div-->
<!-- ###################### UP - DOWN END ###################### -->


 <!-- ###################### FEEDBACK MODAL ###################### -->
 <div class="modal fade inputForm-modal" id="feedbackFormModal" tabindex="-1" role="dialog" aria-labelledby="feedbackFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

        <div class="modal-header" id="feedbackFormModalLabel">
            <h5 class="modal-title">Geri Bildirim Formu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"><svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
        </div>
        <div class="modal-body">
            <form class="mt-0">
                <div class="form-group">
                    <textarea class="form-control" id="feedback_details" name="feedback_details" rows="5" placeholder="Bize iletmek istediğiniz sorunu buraya yazabilirsiniz..."></textarea>
                    <small class="form-text text-muted">Not: Yazdığınız geri bildirim bulunduğunuz sayfa ile ilişkilendirilir. Yani yazdığınız geril bildirim için şu an bulunduğunuz sayfa incelenecektir.</small>
                </div>
                <input type="hidden" id="feedback_page" name="feedback_page" value="<?='https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];?>">
            </form>

        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-light-danger mt-2 mb-2 btn-no-effect cancel_feedback_button">Vazgeç</button>
            <button type="submit" class="btn btn-primary mt-2 mb-2 btn-no-effect send_feedback_button">Gönder</button>
        </div>
        </div>
    </div>
</div>
<!-- ###################### FEEDBACK MODAL END ###################### -->

<!-- BEGIN GLOBAL MANDATORY SCRIPTS -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"
    integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>
<script src="theme/src/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="theme/src/plugins/src/perfect-scrollbar/perfect-scrollbar.min.js"></script>
<script src="theme/src/plugins/src/mousetrap/mousetrap.min.js"></script>
<script src="theme/src/plugins/src/waves/waves.min.js"></script>
<script src="theme/layouts/horizontal-light-menu/app.js"></script>

<?php require_once('custom/js/my_scripts.php'); ?>

<!-- <script src="theme/src/assets/js/elements/tooltip.js"></script> -->

<!-- <script src="theme/src/assets/js/scrollspyNav.js"></script> -->
<script src="theme/src/plugins/src/sweetalerts2/sweetalerts2.min.js"></script>
<!-- <script src="theme/src/plugins/src/sweetalerts2/custom-sweetalert.js"></script> -->
<script src="theme/src/plugins/src/tomSelect/tom-select.base.js"></script>

<script src="theme/src/plugins/src/flatpickr/flatpickr.js"></script>
<!-- END GLOBAL MANDATORY SCRIPTS -->

<!-- toastr -->
<script src="theme/src/plugins/src/notification/snackbar/snackbar.min.js"></script>

<!-- autoComplete -->
<script src="theme/src/plugins/src/autocomplete/autoComplete.min.js"></script>

<!-- BEGIN DATATABLE SCRIPTS -->
<script src="theme/src/plugins/src/table/datatable/datatables.min.js"></script>

<script>
$(document).ready(function(){

    // <!-- DATATABLE SCRIPTS -->
    var zero_config = new DataTable('#zero-config', {
        dom: "<'dt--top-section'<'row'<'col-12 col-sm-6 d-flex justify-content-sm-start justify-content-center'i><'col-12 col-sm-6 d-flex justify-content-sm-end justify-content-center mt-sm-0 mt-3'f>>>" +
            "<'table-responsive'tr>" +
            "<'dt--bottom-section d-sm-flex justify-content-sm-between text-center'<'dt--pages-count  mb-sm-0 mb-3'><'dt--pagination'>>",
        oLanguage: {
            oPaginate: {
                sPrevious: '<<',
                sNext: '>>'
            },
            sInfo: "Listede toplam _TOTAL_ kayıt var",
            sSearch: '🔍',
            sSearchPlaceholder: "Search...",
            // "sLengthMenu": "Results :  _MENU_",
            bLengthChange: false,
        },
        stripeClasses: [],
        paging: false,
        <?php echo $dt_order_by ?? ''; ?>
    });

    // <!-- END DATATABLE SCRIPTS -->
    
    // <!-- AUTOCOMPLETE SCRIPTS -->
    const global_search = new autoComplete({
        selector: "#global_search",
        placeHolder: "Birşeyler ara...",
        threshold: 3, // 👈 En az 3 karakter gereksinimi
        debounce: 1000, // 👈 1 saniye gecikme (1000 ms)
        data: {
            src: async (query) => {
                const response = await fetch(`<?php echo $ajax; ?>?action=global_search&param=${query}`);
                const data = await response.json();
                return data;
            },
            keys: ["ad"], // ekranda görünen alan
            cache: false
        },
        resultItem: {
            highlight: true,
            element: (item, data) => {
                // item.innerHTML = `<span>${data.match}</span>`;
                item.innerHTML = `${data.match}`;
            }
        },
        events: {
            input: {
                selection(event) {
                const selected = event.detail.selection.value;
                document.querySelector("#global_search").value = selected.ad;

                // 👇 url alanına yönlendirme yapılıyor
                window.location.href = selected.url;
                }
            }
        }
    });
    // <!-- END AUTOCOMPLETE SCRIPTS -->

    $(document).on("click", ".showfeedbackFormModal", function() {
        $('#feedbackFormModal').modal('toggle');
    });

    $(document).on("click", ".send_feedback_button", function() {
        
        let feedback_details = $('#feedback_details').val();
        let feedback_page = $('#feedback_page').val();

        $.ajax({
            type: "POST",
            url: '<?php echo $ajax; ?>',
            data: { action: 'feedback_ekle', feedback_details: feedback_details, feedback_page: feedback_page },
            success: function(data) {
                if (data >= 1) {
                    Swal.fire({
                        position: 'center',
                        icon: 'success',
                        title: 'Geri bildiriniz için teşekkürler',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    $('#feedbackFormModal').modal('hide');
                } else {
                    Swal.fire({
                        position: 'center',
                        icon: 'danger',
                        title: 'Hata oluştu. Lüften daha sonra tekrar deneyin',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
                
            }
        });

    });

    $(document).on("click", ".cancel_feedback_button", function() {
        $('#feedbackFormModal').modal('hide');
    });

    $(document).on("click", ".showChModal", function() {
        let modal_adi = $(this).data('modalname');
        $('#' + modal_adi + ' .modalChForm').trigger("reset");
        $('#ch_id').val("0");
        $('#action').val("cari_ekle");
        $('#' + modal_adi).modal('show');
    });
    $(document).on("click", ".showShModal", function() {
        let modal_adi = $(this).data('modalname');
        $('#' + modal_adi + ' .modalChForm').trigger("reset");
        $('#ch_id').val("0");
        $('#action').val("cari_ekle");
        $('#' + modal_adi).modal('show');
    });
    $(document).on("change", "#calisma_modu", function() {
        let calisma_modu = $(this).val();
        $('.vuiin_menu_items').hide();
        $('.'+calisma_modu).show();

        $.ajax({
            type: "POST",
            url: '<?php echo $ajax; ?>',
            data: { action: 'calisma_modu', value: calisma_modu },
            success: function(response) {
                console.log('Calisma Modu : ' + calisma_modu);
                $('#header_dashboard').attr('href', response);
            }
        });

    });

});

function fiyatInputKontrol(input) {
    let v = input.value;

    // virgülü noktaya çevir
    v = v.replace(/,/g, '.');

    // rakam ve nokta dışındakileri sil
    v = v.replace(/[^0-9.]/g, '');

    // sadece 1 tane nokta olsun
    const parts = v.split('.');
    if (parts.length > 2) {
        v = parts[0] + '.' + parts.slice(1).join('');
    }

    input.value = v;
}
</script>