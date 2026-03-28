document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-checkout-root]');

    if (!root) {
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const summaryUrl = root.dataset.summaryUrl;
    const summaryShell = root.querySelector('[data-summary-shell]');
    const addressList = root.querySelector('[data-address-list]');
    const formShell = root.querySelector('[data-address-form-shell]');
    const form = root.querySelector('[data-checkout-address-form]');
    const feedback = root.querySelector('[data-form-feedback]');
    const toastStack = root.querySelector('[data-toast-stack]');
    const deleteModalElement = document.getElementById('checkoutDeleteModal');
    const deleteLabel = deleteModalElement?.querySelector('[data-delete-label]');
    const deleteConfirm = deleteModalElement?.querySelector('[data-delete-confirm]');
    const editingAddressId = form?.querySelector('[data-editing-address-id]');
    const addressMethod = form?.querySelector('[data-address-method]');
    const addressFormTitle = root.querySelector('[data-address-form-title]');
    const addressFormCopy = root.querySelector('[data-address-form-copy]');
    const addressFormChip = root.querySelector('[data-address-form-chip]');
    const addressSubmit = form?.querySelector('[data-address-submit]');
    const addressCancel = form?.querySelector('[data-address-cancel]');
    const addressDefaultCheckbox = form?.querySelector('[data-address-default-checkbox]');
    const pincodeStatus = form?.querySelector('[data-pincode-status]');
    const countrySelect = form?.querySelector('[data-location-country]');
    const countySelect = form?.querySelector('[data-location-county-select]');
    const subCountySelect = form?.querySelector('[data-location-sub-county-select]');
    const countyText = form?.querySelector('[data-location-county-text]');
    const subCountyText = form?.querySelector('[data-location-sub-county-text]');
    const kenyaCountyWrapper = form?.querySelector('[data-location-mode="kenya-county"]');
    const kenyaSubCountyWrapper = form?.querySelector('[data-location-mode="kenya-sub-county"]');
    const globalCountyWrapper = form?.querySelector('[data-location-mode="global-county"]');
    const globalSubCountyWrapper = form?.querySelector('[data-location-mode="global-sub-county"]');
    const countyStatus = form?.querySelector('[data-county-status]');
    const subCountyStatus = form?.querySelector('[data-sub-county-status]');
    const pincodeInput = form?.querySelector('[name="address_pincode"]');
    const pincodeCheck = form?.querySelector('[data-pincode-check]');
    const fields = form ? {
        fullName: document.getElementById('full_name'),
        phone: document.getElementById('phone'),
        line1: document.getElementById('address_line1'),
        line2: document.getElementById('address_line2'),
        estate: document.getElementById('address_estate'),
        landmark: document.getElementById('address_landmark'),
        pincode: document.getElementById('address_pincode'),
    } : {};

    let activeId = root.dataset.selectedAddressId || '';
    let currentAddressCount = Number(root.dataset.addressCount || 0);
    let pendingDelete = null;
    let pendingCounty = form?.dataset.selectedCounty || countyText?.value || '';
    let pendingSubCounty = form?.dataset.selectedSubCounty || subCountyText?.value || '';

    const showModal = () => {
        if (deleteModalElement && window.jQuery) {
            window.jQuery(deleteModalElement).modal('show');
            return true;
        }

        return false;
    };

    const hideModal = () => {
        if (deleteModalElement && window.jQuery) {
            window.jQuery(deleteModalElement).modal('hide');
        }
    };

    const setSummaryLoading = (value) => {
        if (summaryShell) {
            summaryShell.classList.toggle('is-loading', value);
        }
    };

    const setStatus = (element, state, message) => {
        if (!element) {
            return;
        }

        element.dataset.state = state || '';
        element.textContent = message || '';
        element.classList.toggle('is-visible', Boolean(message));
    };

    const setFeedback = (type, message) => {
        if (!feedback) {
            return;
        }

        if (!message) {
            feedback.innerHTML = '';
            return;
        }

        const toneClass = type === 'success'
            ? 'alert-success'
            : type === 'info'
                ? 'alert-info'
                : 'alert-danger';

        feedback.innerHTML = `<div class="alert ${toneClass} checkout-inline-alert mb-3">${message}</div>`;
    };

    const showToast = (type, message) => {
        if (!toastStack || !message) {
            return;
        }

        const tone = type === 'success' ? 'success' : type === 'info' ? 'info' : 'danger';
        const title = tone === 'success' ? 'Checkout updated' : tone === 'info' ? 'Checkout notice' : 'Checkout action failed';
        const toast = document.createElement('div');
        const header = document.createElement('div');
        const strong = document.createElement('strong');
        const close = document.createElement('button');
        const closeIcon = document.createElement('span');
        const body = document.createElement('div');

        toast.className = 'toast checkout-toast';
        toast.dataset.tone = tone;
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.setAttribute('aria-atomic', 'true');

        header.className = 'toast-header';
        strong.className = 'mr-auto';
        strong.textContent = title;

        close.type = 'button';
        close.className = 'ml-2 mb-1 close';
        close.setAttribute('data-dismiss', 'toast');
        close.setAttribute('aria-label', 'Close');

        closeIcon.setAttribute('aria-hidden', 'true');
        closeIcon.innerHTML = '&times;';

        close.appendChild(closeIcon);
        header.appendChild(strong);
        header.appendChild(close);

        body.className = 'toast-body';
        body.textContent = message;

        toast.appendChild(header);
        toast.appendChild(body);
        toastStack.appendChild(toast);

        if (!window.jQuery) {
            window.setTimeout(() => toast.remove(), 3500);
            return;
        }

        const $toast = window.jQuery(toast);
        $toast.toast({ delay: 3500 });
        $toast.on('hidden.bs.toast', function () {
            toast.remove();
        });
        $toast.toast('show');
    };

    const clearErrors = () => {
        if (!form) {
            return;
        }

        form.querySelectorAll('.is-invalid').forEach((element) => {
            element.classList.remove('is-invalid');
        });

        form.querySelectorAll('[data-error-for]').forEach((element) => {
            element.textContent = '';
        });
    };

    const applyErrors = (errors) => {
        Object.entries(errors || {}).forEach(([name, messages]) => {
            const message = Array.isArray(messages) ? messages[0] : messages;
            const matchingFields = new Set();
            const namedField = form?.querySelector(`[name="${name}"]`);

            if (namedField) {
                matchingFields.add(namedField);
            }

            form?.querySelectorAll(`[data-location-name="${name}"]`).forEach((element) => {
                matchingFields.add(element);
            });

            matchingFields.forEach((element) => {
                element.classList.add('is-invalid');
            });

            form?.querySelectorAll(`[data-error-for="${name}"]`).forEach((element) => {
                element.textContent = message;
            });
        });
    };

    const setSelected = (id) => {
        activeId = id ? String(id) : '';
        root.dataset.selectedAddressId = activeId;

        addressList?.querySelectorAll('[data-address-card]').forEach((card) => {
            const selected = (card.dataset.addressId || '') === activeId;
            const button = card.querySelector('[data-address-select]');

            card.classList.toggle('is-selected', selected);

            if (!button) {
                return;
            }

            button.classList.toggle('btn-primary', selected);
            button.classList.toggle('btn-outline-primary', !selected);
            button.textContent = selected ? 'Selected for payment' : 'Use this address';
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
    };

    const renderSummary = (payload) => {
        if (summaryShell && Object.prototype.hasOwnProperty.call(payload || {}, 'summary_html')) {
            summaryShell.innerHTML = payload.summary_html;
        }
    };

    const renderAddressList = (payload) => {
        if (addressList && Object.prototype.hasOwnProperty.call(payload || {}, 'addresses_html')) {
            addressList.innerHTML = payload.addresses_html;
        }
    };

    const updateAddressCount = (payload) => {
        if (!Object.prototype.hasOwnProperty.call(payload || {}, 'address_count')) {
            return;
        }

        currentAddressCount = Number(payload.address_count || 0);
        root.dataset.addressCount = String(currentAddressCount);
    };

    const applyStatePayload = (payload) => {
        updateAddressCount(payload);
        renderAddressList(payload);
        renderSummary(payload);

        if (Object.prototype.hasOwnProperty.call(payload || {}, 'selected_address_id')) {
            setSelected(payload.selected_address_id || '');
        }
    };

    const parseJson = async (response) => response.json().catch(() => ({}));

    const postEncoded = async (url, payload = {}) => {
        const body = new URLSearchParams();

        Object.entries(payload).forEach(([key, value]) => {
            if (value !== null && value !== undefined) {
                body.append(key, value);
            }
        });

        return fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-CSRF-TOKEN': csrf,
            },
            credentials: 'same-origin',
            body,
        });
    };

    const refreshSummary = async (payload = {}) => {
        setSummaryLoading(true);

        try {
            const response = await postEncoded(summaryUrl, payload);
            const json = await parseJson(response);

            if (!response.ok) {
                throw new Error(json.message || 'Unable to refresh checkout totals right now.');
            }

            renderSummary(json);

            if (!payload.preview_only && Object.prototype.hasOwnProperty.call(json, 'selected_address_id')) {
                setSelected(json.selected_address_id || '');
            }

            return json;
        } finally {
            setSummaryLoading(false);
        }
    };

    const resetSelect = (select, placeholder) => {
        if (!select) {
            return;
        }

        select.innerHTML = '';

        const option = document.createElement('option');
        option.value = '';
        option.textContent = placeholder;
        select.appendChild(option);
    };

    const getSelectedCountryOption = () => countrySelect?.options[countrySelect.selectedIndex] || null;
    const getSelectedCountyOption = () => countySelect?.options[countySelect.selectedIndex] || null;
    const isKenyaSelected = () => (countrySelect?.value || '').trim().toLowerCase() === 'kenya';
    const selectedCountryId = () => getSelectedCountryOption()?.dataset.countryId || '';
    const selectedCountyId = () => getSelectedCountyOption()?.dataset.countyId || '';

    const setKenyaMode = (enabled) => {
        if (!countySelect || !subCountySelect || !countyText || !subCountyText) {
            return;
        }

        kenyaCountyWrapper.hidden = !enabled;
        kenyaSubCountyWrapper.hidden = !enabled;
        globalCountyWrapper.hidden = enabled;
        globalSubCountyWrapper.hidden = enabled;

        countySelect.disabled = !enabled;
        subCountySelect.disabled = !enabled;
        countySelect.required = enabled;
        subCountySelect.required = enabled;

        countyText.disabled = enabled;
        subCountyText.disabled = enabled;
        countyText.required = !enabled && countyText.dataset.manualRequired === 'true';
        subCountyText.required = !enabled && subCountyText.dataset.manualRequired === 'true';

        if (enabled) {
            countySelect.setAttribute('name', 'address_county');
            subCountySelect.setAttribute('name', 'address_sub_county');
            countyText.removeAttribute('name');
            subCountyText.removeAttribute('name');
            return;
        }

        countyText.setAttribute('name', 'address_county');
        subCountyText.setAttribute('name', 'address_sub_county');
        countySelect.removeAttribute('name');
        subCountySelect.removeAttribute('name');
    };

    const populateCounties = async (countryId, selectedCounty = '') => {
        if (!countySelect || !subCountySelect) {
            return;
        }

        if (!countryId) {
            resetSelect(countySelect, 'Select a county');
            resetSelect(subCountySelect, 'Select a sub-county');
            setStatus(countyStatus, '', '');
            setStatus(subCountyStatus, '', '');
            return;
        }

        countySelect.disabled = true;
        subCountySelect.disabled = true;
        resetSelect(countySelect, 'Loading counties...');
        resetSelect(subCountySelect, 'Select a sub-county');
        setStatus(countyStatus, 'loading', 'Loading counties for the selected country...');
        setStatus(subCountyStatus, '', '');

        try {
            const response = await fetch(`${form.dataset.countiesUrl}?country_id=${encodeURIComponent(countryId)}`, {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error();
            }

            const payload = await parseJson(response);
            const counties = Array.isArray(payload.counties) ? payload.counties : [];

            resetSelect(countySelect, counties.length ? 'Select a county' : 'No counties available');

            counties.forEach((county) => {
                const option = document.createElement('option');
                option.value = county.name;
                option.textContent = county.name;
                option.dataset.countyId = county.id;

                if (selectedCounty && selectedCounty === county.name) {
                    option.selected = true;
                }

                countySelect.appendChild(option);
            });

            countySelect.disabled = false;
            subCountySelect.disabled = false;
            setStatus(
                countyStatus,
                counties.length ? 'success' : 'error',
                counties.length ? `${counties.length} counties loaded.` : 'No counties were found for the selected country.'
            );
        } catch (error) {
            resetSelect(countySelect, 'Unable to load counties');
            resetSelect(subCountySelect, 'Select a sub-county');
            countySelect.disabled = false;
            subCountySelect.disabled = false;
            setStatus(countyStatus, 'error', 'Unable to load counties right now. Try changing the country again.');
            setStatus(subCountyStatus, '', '');
        }
    };

    const populateSubCounties = async (countyId, selectedSubCounty = '') => {
        if (!subCountySelect) {
            return;
        }

        if (!countyId) {
            resetSelect(subCountySelect, 'Select a sub-county');
            setStatus(subCountyStatus, '', '');
            return;
        }

        subCountySelect.disabled = true;
        resetSelect(subCountySelect, 'Loading sub-counties...');
        setStatus(subCountyStatus, 'loading', 'Loading sub-counties for the selected county...');

        try {
            const response = await fetch(`${form.dataset.subCountiesUrl}?county_id=${encodeURIComponent(countyId)}`, {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error();
            }

            const payload = await parseJson(response);
            const subCounties = Array.isArray(payload.sub_counties) ? payload.sub_counties : [];

            resetSelect(subCountySelect, subCounties.length ? 'Select a sub-county' : 'No sub-counties available');

            subCounties.forEach((subCounty) => {
                const option = document.createElement('option');
                option.value = subCounty.name;
                option.textContent = subCounty.name;

                if (selectedSubCounty && selectedSubCounty === subCounty.name) {
                    option.selected = true;
                }

                subCountySelect.appendChild(option);
            });

            subCountySelect.disabled = false;
            setStatus(
                subCountyStatus,
                subCounties.length ? 'success' : 'error',
                subCounties.length ? `${subCounties.length} sub-counties loaded.` : 'No sub-counties were found for the selected county.'
            );
        } catch (error) {
            resetSelect(subCountySelect, 'Unable to load sub-counties');
            subCountySelect.disabled = false;
            setStatus(subCountyStatus, 'error', 'Unable to load sub-counties right now. Try selecting the county again.');
        }
    };

    const syncLocationMode = async () => {
        const kenyaSelected = isKenyaSelected();

        setKenyaMode(kenyaSelected);

        if (!kenyaSelected) {
            if (countyText) {
                countyText.value = pendingCounty;
            }

            if (subCountyText) {
                subCountyText.value = pendingSubCounty;
            }

            resetSelect(countySelect, 'Select a county');
            resetSelect(subCountySelect, 'Select a sub-county');
            setStatus(countyStatus, '', '');
            setStatus(subCountyStatus, '', '');
            pendingCounty = '';
            pendingSubCounty = '';
            return;
        }

        await populateCounties(selectedCountryId(), pendingCounty);
        await populateSubCounties(selectedCountyId(), pendingSubCounty);

        pendingCounty = '';
        pendingSubCounty = '';
    };

    const applySelections = async ({ country = countrySelect?.value || '', county = '', subCounty = '' } = {}) => {
        if (countrySelect && country) {
            countrySelect.value = country;
        }

        pendingCounty = county || '';
        pendingSubCounty = subCounty || '';

        if (countyText) {
            countyText.value = pendingCounty;
        }

        if (subCountyText) {
            subCountyText.value = pendingSubCounty;
        }

        await syncLocationMode();
    };

    const setCreateMode = async ({ preserveFeedback = false } = {}) => {
        if (!form) {
            return;
        }

        formShell?.classList.remove('is-editing');
        addressFormTitle.textContent = 'Add a new address';
        addressFormCopy.textContent = 'Capture the shipping recipient, full street details, and pincode here. The order summary previews delivery as soon as the pincode is checked.';
        addressFormChip.innerHTML = '<i class="fa fa-plus-circle"></i>New shipping address';
        addressSubmit.textContent = 'Save address';
        addressCancel.classList.add('d-none');
        form.action = form.dataset.storeAction;
        editingAddressId.value = '';
        addressMethod.value = '';
        addressMethod.removeAttribute('name');
        fields.fullName.value = form.dataset.createFullName || '';
        fields.phone.value = form.dataset.createPhone || '';
        fields.line1.value = '';
        fields.line2.value = '';
        fields.estate.value = '';
        fields.landmark.value = '';
        fields.pincode.value = '';
        addressDefaultCheckbox.checked = currentAddressCount === 0;
        clearErrors();

        if (!preserveFeedback) {
            setFeedback('', '');
        }

        setStatus(pincodeStatus, '', '');

        await applySelections({
            country: form.dataset.createCountry || 'Kenya',
            county: '',
            subCounty: '',
        });
    };

    const setEditMode = async (button) => {
        if (!form) {
            return;
        }

        formShell?.classList.add('is-editing');
        addressFormTitle.textContent = 'Edit saved address';
        addressFormCopy.textContent = 'Update the delivery details below. The selected checkout address and order summary will stay in sync after saving.';
        addressFormChip.innerHTML = '<i class="fa fa-pen"></i>Edit in checkout';
        addressSubmit.textContent = 'Update address';
        addressCancel.classList.remove('d-none');
        form.action = button.dataset.updateUrl;
        editingAddressId.value = button.dataset.addressId || '';
        addressMethod.value = 'PUT';
        addressMethod.setAttribute('name', '_method');
        fields.fullName.value = button.dataset.addressFullName || form.dataset.createFullName || '';
        fields.phone.value = button.dataset.addressPhone || form.dataset.createPhone || '';
        fields.line1.value = button.dataset.addressLine1 || '';
        fields.line2.value = button.dataset.addressLine2 || '';
        fields.estate.value = button.dataset.addressEstate || '';
        fields.landmark.value = button.dataset.addressLandmark || '';
        fields.pincode.value = button.dataset.addressPincode || '';
        addressDefaultCheckbox.checked = button.dataset.addressDefault === '1';
        clearErrors();
        setFeedback('', '');
        setStatus(pincodeStatus, '', '');

        await applySelections({
            country: button.dataset.addressCountry || 'Kenya',
            county: button.dataset.addressCounty || '',
            subCounty: button.dataset.addressSubCounty || '',
        });

        formShell?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    };

    countrySelect?.addEventListener('change', async function () {
        if (isKenyaSelected()) {
            pendingCounty = '';
            pendingSubCounty = '';

            if (countyText) {
                countyText.value = '';
            }

            if (subCountyText) {
                subCountyText.value = '';
            }
        } else {
            pendingCounty = countyText?.value || '';
            pendingSubCounty = subCountyText?.value || '';
        }

        await syncLocationMode();
    });

    countySelect?.addEventListener('change', async function () {
        pendingSubCounty = '';
        await populateSubCounties(selectedCountyId(), '');
    });

    pincodeInput?.addEventListener('input', function () {
        setStatus(pincodeStatus, '', '');
    });

    addressCancel?.addEventListener('click', function () {
        setCreateMode();
    });

    addressList?.addEventListener('click', async function (event) {
        const selectButton = event.target.closest('[data-address-select]');
        const editButton = event.target.closest('[data-address-edit]');
        const deleteButton = event.target.closest('[data-address-delete]');

        if (selectButton) {
            event.preventDefault();
            setFeedback('', '');
            setStatus(pincodeStatus, '', '');
            setSummaryLoading(true);

            try {
                const response = await fetch(selectButton.dataset.selectUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    credentials: 'same-origin',
                });
                const json = await parseJson(response);

                if (!response.ok) {
                    throw new Error(json.message || 'Unable to update the selected delivery address right now.');
                }

                renderSummary(json);
                setSelected(json.selected_address_id || '');
            } catch (error) {
                setFeedback('error', error.message || 'Unable to update the selected delivery address right now.');
                showToast('error', error.message || 'Unable to update the selected delivery address right now.');
            } finally {
                setSummaryLoading(false);
            }

            return;
        }

        if (editButton) {
            event.preventDefault();
            await setEditMode(editButton);
            return;
        }

        if (!deleteButton) {
            return;
        }

        event.preventDefault();

        pendingDelete = {
            id: deleteButton.dataset.addressId || '',
            label: deleteButton.dataset.addressLabel || 'this saved address',
            url: deleteButton.dataset.deleteUrl || '',
        };

        if (deleteLabel) {
            deleteLabel.textContent = pendingDelete.label;
        }

        if (!showModal() && window.confirm(`Delete ${pendingDelete.label}?`)) {
            deleteConfirm?.click();
        }
    });

    deleteConfirm?.addEventListener('click', async function () {
        if (!pendingDelete?.url) {
            return;
        }

        const originalText = deleteConfirm.textContent;
        deleteConfirm.disabled = true;
        deleteConfirm.textContent = 'Deleting...';
        setFeedback('', '');
        setSummaryLoading(true);

        try {
            const response = await fetch(pendingDelete.url, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                credentials: 'same-origin',
            });
            const json = await parseJson(response);

            if (!response.ok) {
                throw new Error(json.message || 'Unable to delete the delivery address right now.');
            }

            applyStatePayload(json);

            if (editingAddressId?.value && editingAddressId.value === pendingDelete.id) {
                await setCreateMode({ preserveFeedback: true });
            }

            setFeedback('success', json.message || 'The delivery address has been deleted.');
            showToast('success', json.message || 'The delivery address has been deleted.');
            hideModal();
            pendingDelete = null;
        } catch (error) {
            setFeedback('error', error.message || 'Unable to delete the delivery address right now.');
            showToast('error', error.message || 'Unable to delete the delivery address right now.');
        } finally {
            setSummaryLoading(false);
            deleteConfirm.disabled = false;
            deleteConfirm.textContent = originalText;
        }
    });

    if (deleteModalElement && window.jQuery) {
        window.jQuery(deleteModalElement).on('hidden.bs.modal', function () {
            pendingDelete = null;
        });
    }

    pincodeCheck?.addEventListener('click', async function () {
        clearErrors();
        setFeedback('', '');

        const payload = await refreshSummary({
            pincode: pincodeInput?.value.trim() || '',
            preview_only: '1',
        }).catch(() => null);

        if (!payload) {
            setStatus(pincodeStatus, 'error', 'Unable to preview delivery right now.');
            return;
        }

        const tone = payload.summary?.statusTone || 'info';

        setStatus(
            pincodeStatus,
            tone === 'success' ? 'success' : tone === 'danger' ? 'error' : 'info',
            payload.summary?.statusMessage || 'Delivery preview updated.'
        );
    });

    form?.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearErrors();
        setFeedback('', '');
        setSummaryLoading(true);

        try {
            const response = await fetch(form.getAttribute('action'), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                credentials: 'same-origin',
                body: new FormData(form),
            });
            const json = await parseJson(response);

            if (!response.ok) {
                if (response.status === 422) {
                    applyErrors(json.errors || {});
                    setFeedback('error', json.message || 'Please correct the highlighted fields and try again.');
                    return;
                }

                throw new Error(json.message || 'Unable to save the address right now. Please try again.');
            }

            applyStatePayload(json);
            await setCreateMode({ preserveFeedback: true });
            setFeedback('success', json.message || 'The address has been saved.');
            showToast('success', json.message || 'The address has been saved.');
        } catch (error) {
            setFeedback('error', error.message || 'Unable to save the address right now. Please try again.');
            showToast('error', error.message || 'Unable to save the address right now. Please try again.');
        } finally {
            setSummaryLoading(false);
        }
    });

    if (form) {
        form.__locationApi = {
            applySelections,
        };
    }

    syncLocationMode();

    const syncCartSummary = () => refreshSummary(activeId ? { address_id: activeId } : {}).catch(() => null);

    window.addEventListener('focus', syncCartSummary);

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            syncCartSummary();
        }
    });

    window.addEventListener('storage', function (event) {
        if (event.key === 'shera_cart_updated_at') {
            syncCartSummary();
        }
    });
});
