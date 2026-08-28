@push('scripts')

<script>

let unitIndex = $('#unitsTable tbody tr').length;

if(unitIndex === 0){
    addUnitRow();
}
/*
|--------------------------------------------------------------------------
| إضافة صف جديد
|--------------------------------------------------------------------------
*/

function addUnitRow(){

    let row = $('#unitRowTemplate').html();

    row = row.replaceAll('{index}', unitIndex);

    $('#unitsTable tbody').append(row);

    unitIndex++;

}


/*
|--------------------------------------------------------------------------
| إضافة وحدة
|--------------------------------------------------------------------------
*/

$('#addUnit').click(function(){

    addUnitRow();

});


/*
|--------------------------------------------------------------------------
| حذف وحدة
|--------------------------------------------------------------------------
*/

$(document).on('click','.removeRow',function(){

    if($('#unitsTable tbody tr').length <= 1){

        Swal.fire({

            icon:'warning',

            title:'تنبيه',

            text:'يجب أن يحتوي الصنف على وحدة واحدة على الأقل.'

        });

        return;
    }

    $(this).closest('tr').remove();

});


/*
|--------------------------------------------------------------------------
| الوحدة الافتراضية
|--------------------------------------------------------------------------
*/

$(document).on('change','.defaultUnit',function(){

    $('.defaultValue').val(0);

    $(this)
        .closest('tr')
        .find('.defaultValue')
        .val(1);

});


/*
|--------------------------------------------------------------------------
| منع تكرار الوحدة
|--------------------------------------------------------------------------
*/

$(document).on('change','.unit-select',function(){

    let selected=[];

    let duplicated=false;

    $('.unit-select').each(function(){

        let value=$(this).val();

        if(value=='')
            return;

        if(selected.includes(value)){

            duplicated=true;

        }

        selected.push(value);

    });

    if(duplicated){

        Swal.fire({

            icon:'warning',

            title:'تنبيه',

            text:'تم اختيار نفس الوحدة أكثر من مرة.'

        });

        $(this).val('');

    }

});


/*
|--------------------------------------------------------------------------
| معاينة الصور
|--------------------------------------------------------------------------
*/

$('#productImages').change(function(){

    $('#imagePreview').html('');

    Array.from(this.files).forEach(file=>{

        let reader=new FileReader();

        reader.onload=function(e){

            $('#imagePreview').append(

                `<img src="${e.target.result}"
                      class="img-thumbnail"
                      style="width:120px;height:120px;object-fit:cover;">`

            );

        }

        reader.readAsDataURL(file);

    });

});

 $(document).on('click', '#deleteImage', function (e) {
        e.preventDefault();

        let imageId = $(this).data('id');

        Swal.fire({
            title: 'حذف الصورة؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'حذف',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "{{ url('/product-images') }}/" + imageId,
                type: "DELETE",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {
                    $('#image-box-' + imageId).remove();

                    Swal.fire({
                        icon: 'success',
                        title: 'تم',
                        text: response.message,
                        timer: 1000,
                        showConfirmButton: false
                    });
                },
                error: function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: xhr.responseJSON?.message ?? 'فشل حذف الصورة'
                    });
                }
            });
        });
    });

/*
|--------------------------------------------------------------------------
| حفظ المنتج
|--------------------------------------------------------------------------
*/

$('#productForm').submit(function(e){

    e.preventDefault();

    let formData=new FormData(this);

    $.ajax({

        url:$(this).attr('action'),

        type:'POST',

        data:formData,

        processData:false,

        contentType:false,

        beforeSend:function(){

            $('#btnSave').prop('disabled',true);

            $('#formErrors').html('');

        },

        success:function(response){

            Swal.fire({

                icon:'success',

                title:'تم الحفظ',

                text:response.message,

                timer:1500,

                showConfirmButton:false

            });

            setTimeout(function(){

                window.location="{{ route('products.index') }}";

            },1500);

        },

        error:function(xhr){

            $('#btnSave').prop('disabled',false);

            if(xhr.status===422){

                let html='<div class="alert alert-danger"><ul>';

                $.each(xhr.responseJSON.errors,function(key,value){

                    html+='<li>'+value[0]+'</li>';

                });

                html+='</ul></div>';

                $('#formErrors').html(html);

            }else{

                Swal.fire({

                    icon:'error',

                    title:'خطأ',

                    text:'حدث خطأ أثناء الحفظ.'

                });

            }

        }

    });

   

});

</script>

@endpush