@php
    $cropWidth = $field['crop_width'] ?? 200;
    $cropHeight = $field['crop_height'] ?? 80;
    $logoPath = old(square_brackets_to_dots($field['name'])) ?? $field['value'] ?? $field['default'] ?? '';
    $logoUrl = $logoPath ? \App\Support\CompanySettings::logoUrl($logoPath) : null;
    $fieldId = 'company_logo_' . preg_replace('/[^a-z0-9_]/i', '_', $field['name']);

    $field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
    $field['wrapper']['data-init-function'] = $field['wrapper']['data-init-function'] ?? 'bpFieldInitCompanyLogoElement';
@endphp

@include('crud::fields.inc.wrapper_start')
<label>{!! $field['label'] !!}</label>
@include('crud::fields.inc.translatable_icon')

<div id="{{ $fieldId }}_wrapper" data-crop-width="{{ $cropWidth }}" data-crop-height="{{ $cropHeight }}" data-modal-id="{{ $fieldId }}_modal">
    <div class="company-logo-preview mb-3 {{ $logoUrl ? '' : 'd-none' }}">
        <img src="{{ $logoUrl }}" alt="Company logo preview" class="company-logo-preview-image"
            style="width: {{ $cropWidth }}px; height: {{ $cropHeight }}px; object-fit: contain; border: 1px solid #e4e7ea; border-radius: 4px; background: #fff;">
        <div class="mt-2">
            <button type="button" class="btn btn-sm btn-outline-danger company-logo-clear-btn">
                <i class="la la-trash"></i> Remove Logo
            </button>
        </div>
    </div>

    <input type="file" class="form-control company-logo-file-input" accept="image/*">
    <input type="hidden" name="company_logo_cropped" class="company-logo-cropped-input" value="">
    <input type="hidden" name="company_logo_clear" class="company-logo-clear-input" value="0">

    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
</div>

@include('crud::fields.inc.wrapper_end')

@push('crud_fields_styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <style>
        .company-logo-crop-container {
            max-height: 420px;
            overflow: hidden;
            background: #f8f9fa;
        }

        .company-logo-crop-container img {
            display: block;
            max-width: 100%;
        }
    </style>
@endpush

@push('after_scripts')
    <div class="modal fade company-logo-crop-modal" id="{{ $fieldId }}_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crop Company Logo ({{ $cropWidth }}×{{ $cropHeight }}px)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="company-logo-crop-container">
                        <img src="" alt="Crop preview" class="company-logo-crop-image w-100">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary company-logo-crop-save-btn">Crop & Use</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('crud_fields_scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <script>
        function bpFieldInitCompanyLogoElement(element) {
            const wrapper = element.find('[id$="_wrapper"]').first();
            if (!wrapper.length) {
                return;
            }

            const cropWidth = parseInt(wrapper.data('crop-width'), 10) || 200;
            const cropHeight = parseInt(wrapper.data('crop-height'), 10) || 80;
            const modalEl = $('#' + wrapper.data('modal-id'));
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl[0]);
            const cropImage = modalEl.find('.company-logo-crop-image');
            const fileInput = wrapper.find('.company-logo-file-input');
            const croppedInput = wrapper.find('.company-logo-cropped-input');
            const clearInput = wrapper.find('.company-logo-clear-input');
            const previewWrap = wrapper.find('.company-logo-preview');
            const previewImage = wrapper.find('.company-logo-preview-image');
            const clearBtn = wrapper.find('.company-logo-clear-btn');
            const saveBtn = modalEl.find('.company-logo-crop-save-btn');
            let cropper = null;

            function destroyCropper() {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
            }

            fileInput.on('change', function () {
                const file = this.files[0];
                if (!file) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    destroyCropper();
                    cropImage.attr('src', event.target.result);
                    modal.show();

                    cropImage.off('load').on('load', function () {
                        destroyCropper();
                        cropper = new Cropper(cropImage[0], {
                            aspectRatio: cropWidth / cropHeight,
                            viewMode: 1,
                            autoCropArea: 1,
                            responsive: true,
                            background: false,
                        });
                    });
                };
                reader.readAsDataURL(file);
            });

            saveBtn.on('click', function () {
                if (!cropper) {
                    return;
                }

                const canvas = cropper.getCroppedCanvas({
                    width: cropWidth,
                    height: cropHeight,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });

                const dataUrl = canvas.toDataURL('image/png');
                croppedInput.val(dataUrl);
                clearInput.val('0');
                previewImage.attr('src', dataUrl);
                previewWrap.removeClass('d-none');
                fileInput.val('');
                modal.hide();
                destroyCropper();
            });

            modalEl.on('hidden.bs.modal', function () {
                destroyCropper();
                fileInput.val('');
            });

            clearBtn.on('click', function () {
                croppedInput.val('');
                clearInput.val('1');
                previewWrap.addClass('d-none');
                previewImage.attr('src', '');
                fileInput.val('');
            });
        }
    </script>
@endpush