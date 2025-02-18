jQuery(document).ready(function($) {
    $('#custom_product_field_type').change(function() {
        var selectedType = $(this).val();
        SetFieldProperty('customInputType', selectedType);
    });

    $(document).on('change', '#custom_product_field_type', function() {
        var selectedType = $(this).val();
        var field = GetSelectedField();
        field.customInputType = selectedType;
        SetFieldProperty('customInputType', selectedType);
        UpdateFieldChoices(field);
    });

    function UpdateFieldChoices(field) {
        if (field.customInputType === 'multiselect') {
            $('.gfield_select').attr('multiple', 'multiple');
        } else if (field.customInputType === 'checkbox') {
            $('.gf-product-checkbox-group').html('');
            field.choices.forEach(choice => {
                $('.gf-product-checkbox-group').append(
                    '<label><input type="checkbox" name="input_' + field.id + '[]" value="' + choice.value + 
                    '"> ' + choice.text + '</label><br>'
                );
            });
        }
    }
});
