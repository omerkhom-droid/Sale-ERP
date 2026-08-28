<div class="modal fade"
     id="categoryModal"
     tabindex="-1"
     data-bs-backdrop="static">

    <div class="modal-dialog modal-lg">

        <form id="category_form">

            @csrf

            <input type="hidden"
                   id="category_id"
                   name="category_id">

            <input type="hidden"
                   id="parent_id"
                   name="parent_id">

            <div class="modal-content">

                <div class="modal-header bg-primary text-white">

                    <h5 class="modal-title"
                        id="CategoryModalLabel">

                        إضافة تصنيف

                    </h5>

                    <button type="button"
                            class="btn btn-warning btn-sm"
                            data-bs-dismiss="modal">

                        إغلاق

                    </button>

                </div>

                <div class="modal-body">

                    <div id="form_errors"></div>

                    <div class="row">

                        <div class="col-md-12 mb-3">

                            <label>
                                اسم التصنيف
                            </label>

                            <input type="text"
                                   name="category_name"
                                   id="category_name"
                                   class="form-control">

                        </div>

                        <div class="col-md-12 mb-3">

                            <label>
                                الوصف
                            </label>

                            <textarea
                                name="description"
                                id="description"
                                rows="3"
                                class="form-control"></textarea>

                        </div>

                        <div class="col-md-12">

                            <label>
                                الحالة
                            </label>

                            <select
                                name="is_active"
                                id="is_active"
                                class="form-control">

                                <option value="1">
                                    نشط
                                </option>

                                <option value="0">
                                    غير نشط
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="submit"
                            id="action"
                            class="btn btn-primary w-100">

                        حفظ

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>