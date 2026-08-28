<section class="space-y-4">
    <header>
        <h2>
            حذف الحساب
        </h2>

        <p>
            عند حذف الحساب سيتم حذف بيانات المستخدم نهائيًا. قبل تنفيذ هذا الإجراء تأكد من أنك لا تحتاج إلى هذا الحساب مرة أخرى.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >
        حذف الحساب
    </x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6" dir="rtl">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                هل أنت متأكد من حذف الحساب؟
            </h2>

            <p class="mt-2 text-sm text-gray-600">
                بعد حذف الحساب لن تتمكن من استعادته. يرجى إدخال كلمة المرور لتأكيد عملية الحذف.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="كلمة المرور" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full"
                    placeholder="أدخل كلمة المرور للتأكيد"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 d-flex justify-content-end gap-2">
                <x-secondary-button x-on:click="$dispatch('close')">
                    إلغاء
                </x-secondary-button>

                <x-danger-button>
                    نعم، حذف الحساب
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>