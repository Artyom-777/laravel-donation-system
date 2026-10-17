import React from 'react';
import { createRoot, hydrateRoot } from 'react-dom/client';
import AccountMenu from './react/AccountMenu.jsx';
import CampaignButton from './react/CampaignButton.jsx';
import CampaignStatusFilters from './react/CampaignStatusFilters.jsx';

document.querySelectorAll('[data-account-menu-root]').forEach((root) => {
    const { dataset } = root;

    createRoot(root).render(React.createElement(AccountMenu, {
        csrfToken: dataset.csrfToken,
        currentPasswordError: dataset.currentPasswordError,
        newPasswordError: dataset.newPasswordError,
        passwordUrl: dataset.passwordUrl,
        statusMessage: dataset.statusMessage,
        userName: dataset.userName,
    }));
});

const sidebar = document.querySelector('[data-dashboard-sidebar]');
const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
const sidebarClose = document.querySelector('[data-sidebar-close]');

if (sidebar && sidebarToggle) {
    const mobileQuery = window.matchMedia('(max-width: 760px)');
    let isOpen = !mobileQuery.matches;

    const updateSidebar = () => {
        document.body.classList.toggle('sidebar-open', mobileQuery.matches && isOpen);
        document.body.classList.toggle('sidebar-collapsed', !mobileQuery.matches && !isOpen);
        sidebar.inert = !isOpen;
        sidebar.setAttribute('aria-hidden', String(!isOpen));
        sidebarToggle.setAttribute('aria-expanded', String(isOpen));
    };

    sidebarToggle.addEventListener('click', () => {
        isOpen = !isOpen;
        updateSidebar();
    });

    sidebarClose?.addEventListener('click', () => {
        isOpen = false;
        updateSidebar();
        sidebarToggle.focus();
    });

    mobileQuery.addEventListener('change', () => {
        isOpen = !mobileQuery.matches;
        updateSidebar();
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobileQuery.matches && isOpen) {
            isOpen = false;
            updateSidebar();
            sidebarToggle.focus();
        }
    });

    updateSidebar();
}

const campaignList = document.querySelector('[data-campaign-list]');
const campaignStatusFilters = document.querySelector('[data-campaign-status-filters]');
const campaignSearchFields = document.querySelectorAll('[data-campaign-search]');
const campaignSort = document.querySelector('[data-campaign-sort]');

if (campaignStatusFilters) {
    const { dataset } = campaignStatusFilters;

    createRoot(campaignStatusFilters).render(React.createElement(CampaignStatusFilters, {
        activeStatus: dataset.activeStatus,
        counts: {
            all: Number(dataset.countAll),
            active: Number(dataset.countActive),
            completed: Number(dataset.countCompleted),
            upcoming: Number(dataset.countUpcoming),
        },
        search: dataset.search,
        sort: dataset.sort,
    }));
}

if (campaignList) {
    campaignSearchFields.forEach((searchField) => {
        searchField.addEventListener('input', () => {
            campaignSearchFields.forEach((otherSearchField) => {
                if (otherSearchField !== searchField) {
                    otherSearchField.value = searchField.value;
                }
            });
        });
    });

    campaignSort?.addEventListener('change', () => {
        campaignSort.closest('form')?.requestSubmit();
    });

}

const mountCampaignButtons = (root = document) => {
    root.querySelectorAll('[data-campaign-react-button]:not([data-react-mounted])').forEach((container) => {
        const button = container.querySelector('button');

        if (!button) {
            console.error('A campaign React button mount is missing its fallback button.');

            return;
        }

        container.dataset.reactMounted = 'true';

        hydrateRoot(container, React.createElement(CampaignButton, {
            action: container.dataset.action,
            campaignDescription: container.dataset.campaignDescription,
            campaignId: container.dataset.campaignId,
            campaignStatus: container.dataset.campaignStatus,
            campaignTitle: container.dataset.campaignTitle,
            className: button.className,
            confirmMessage: container.dataset.confirmMessage,
            disabled: button.disabled,
            expanded: container.dataset.expanded,
            label: button.textContent,
            panelId: container.dataset.panelId,
            type: button.type,
        }));
    });
};

mountCampaignButtons();

window.addEventListener('campaign:results-updated', (event) => {
    mountCampaignButtons(event.detail.campaignGrid);
});

const stripeDonationForms = document.querySelectorAll('[data-stripe-donation-form]');
let stripeScriptPromise;

const loadStripeScript = () => {
    if (window.Stripe) {
        return Promise.resolve();
    }

    if (!stripeScriptPromise) {
        stripeScriptPromise = new Promise((resolve, reject) => {
            const existingScript = document.querySelector('script[data-stripe-js]');
            const script = existingScript || document.createElement('script');

            script.addEventListener('load', resolve, { once: true });
            script.addEventListener('error', () => {
                stripeScriptPromise = null;
                script.remove();
                reject(new Error('Stripe could not be loaded. Check your connection and try again.'));
            }, { once: true });

            if (!existingScript) {
                script.src = 'https://js.stripe.com/v3/';
                script.async = true;
                script.dataset.stripeJs = 'true';
                document.head.append(script);
            }
        });
    }

    return stripeScriptPromise;
};

stripeDonationForms.forEach((form) => {
    const message = form.querySelector('[data-stripe-message]');
    const cardContainer = form.querySelector('[data-stripe-card]');
    const token = form.querySelector('input[name="_token"]')?.value;
    let stripe;
    let cardElement;
    let currentPayment;

    const showMessage = (text, isError = false) => {
        if (!message) {
            return;
        }

        message.textContent = text;
        message.classList.toggle('is-error', isError);
    };

    const readResponse = async (response) => {
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const validationMessage = payload.errors
                ? Object.values(payload.errors).flat()[0]
                : null;

            throw new Error(validationMessage || payload.message || 'Something went wrong. Please try again.');
        }

        return payload;
    };

    const initializeStripe = async () => {
        if (cardElement) {
            return;
        }

        const publishableKey = form.dataset.publishableKey;

        if (!publishableKey || !cardContainer || !window.fetch) {
            throw new Error('Card payments are not available right now.');
        }

        await loadStripeScript();
        stripe = window.Stripe(publishableKey);
        cardElement = stripe.elements().create('card', { hidePostalCode: true });
        cardElement.mount(cardContainer);
        cardElement.on('change', (event) => {
            if (event.error) {
                showMessage(event.error.message, true);
            } else {
                showMessage('');
            }
        });
    };

    form.closest('[data-campaign-donation-panel]')?.addEventListener('campaign:donation-open', () => {
        initializeStripe().catch((error) => showMessage(error.message, true));
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const submitButton = form.querySelector('[type="submit"]');

        if (!(submitButton instanceof HTMLButtonElement)) {
            showMessage('The payment button is unavailable. Please reload the page and try again.', true);

            return;
        }

        submitButton.disabled = true;
        showMessage('Preparing your secure payment…');

        try {
            await initializeStripe();

            if (!currentPayment) {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        donor_phone: form.elements.donor_phone.value,
                        amount: form.elements.amount.value,
                    }),
                });
                const payload = await readResponse(response);

                currentPayment = {
                    donationId: payload.donation_id,
                    clientSecret: payload.client_secret,
                    returnUrl: payload.return_url,
                };
                ['donor_name', 'donor_email', 'donor_phone', 'amount'].forEach((name) => {
                    form.elements[name].readOnly = true;
                });
            }

            const { paymentIntent, error } = await stripe.confirmCardPayment(currentPayment.clientSecret, {
                payment_method: {
                    card: cardElement,
                    billing_details: {
                        name: form.elements.donor_name.value,
                        email: form.elements.donor_email.value,
                        phone: form.elements.donor_phone.value || undefined,
                    },
                },
                return_url: currentPayment.returnUrl,
            });

            if (error) {
                throw new Error(error.message || 'Your card could not be charged. Please try again.');
            }

            if (paymentIntent?.status !== 'succeeded') {
                throw new Error('Your payment is still processing. Please wait before trying again.');
            }

            const confirmationUrl = form.dataset.confirmUrlTemplate.replace(
                '__DONATION_ID__',
                encodeURIComponent(currentPayment.donationId),
            );
            const confirmation = await fetch(confirmationUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({ payment_intent_id: paymentIntent.id }),
            });
            const result = await readResponse(confirmation);

            window.location.assign(result.redirect_url);
        } catch (error) {
            showMessage(error.message, true);
        } finally {
            submitButton.disabled = false;
        }
    });
});

const campaignImageInput = document.querySelector('[data-campaign-image-input]');
const campaignUploadZone = document.querySelector('[data-campaign-upload]');
const campaignImagePreview = document.querySelector('[data-campaign-image-preview]');
const campaignUploadPlaceholder = document.querySelector('[data-campaign-upload-placeholder]');
const campaignUploadFileName = document.querySelector('[data-campaign-file-name]');

if (campaignImageInput && campaignUploadZone && campaignImagePreview && campaignUploadPlaceholder && campaignUploadFileName) {
    const supportedImageTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const maximumImageSize = 2 * 1024 * 1024;
    let campaignImageUrl;

    const showCampaignImage = (file) => {
        if (!supportedImageTypes.includes(file.type)) {
            campaignImageInput.setCustomValidity('Choose a JPG, PNG, or WEBP image.');
            campaignImageInput.reportValidity();

            return;
        }

        if (file.size > maximumImageSize) {
            campaignImageInput.setCustomValidity('Choose an image smaller than 2MB.');
            campaignImageInput.reportValidity();

            return;
        }

        campaignImageInput.setCustomValidity('');

        if (campaignImageUrl) {
            URL.revokeObjectURL(campaignImageUrl);
        }

        campaignImageUrl = URL.createObjectURL(file);
        campaignImagePreview.src = campaignImageUrl;
        campaignImagePreview.hidden = false;
        campaignUploadPlaceholder.hidden = false;
        campaignUploadFileName.textContent = file.name;
    };

    campaignUploadZone.addEventListener('click', () => campaignImageInput.click());
    campaignImageInput.addEventListener('change', () => {
        const selectedFile = campaignImageInput.files?.[0];

        if (selectedFile) {
            showCampaignImage(selectedFile);
        }
    });

    campaignUploadZone.addEventListener('dragover', (event) => {
        event.preventDefault();
        campaignUploadZone.classList.add('is-dragging');
    });

    campaignUploadZone.addEventListener('dragleave', () => {
        campaignUploadZone.classList.remove('is-dragging');
    });

    campaignUploadZone.addEventListener('drop', (event) => {
        event.preventDefault();
        campaignUploadZone.classList.remove('is-dragging');

        const droppedFile = event.dataTransfer?.files[0];

        if (droppedFile) {
            const transfer = new DataTransfer();

            transfer.items.add(droppedFile);
            campaignImageInput.files = transfer.files;
            showCampaignImage(droppedFile);
        }
    });
}

const campaignEditor = document.querySelector('[data-campaign-editor]');

if (campaignEditor) {
    const campaignDescriptionInput = document.querySelector('[data-campaign-description]');
    const campaignForm = campaignEditor.closest('form');

    const syncCampaignDescription = () => {
        if (campaignDescriptionInput) {
            campaignDescriptionInput.value = campaignEditor.innerText.trim();
        }
    };

    campaignEditor.addEventListener('input', syncCampaignDescription);
    campaignForm?.addEventListener('submit', syncCampaignDescription);
    syncCampaignDescription();

    document.querySelectorAll('[data-editor-command]').forEach((button) => {
        button.addEventListener('click', () => {
            const command = button.getAttribute('data-editor-command');

            if (!command) {
                return;
            }

            campaignEditor.focus();

            if (command === 'createLink') {
                const linkUrl = window.prompt('Enter a link URL');

                if (linkUrl) {
                    document.execCommand(command, false, linkUrl);
                }

                return;
            }

            document.execCommand(command, false);
            button.classList.toggle('is-active', document.queryCommandState(command));
        });
    });

    document.querySelector('[data-editor-expand]')?.addEventListener('click', () => {
        campaignEditor.classList.toggle('is-expanded');
    });
}

const donorRowsContainer = document.querySelector('[data-donor-rows]');
const donorRows = donorRowsContainer ? Array.from(donorRowsContainer.querySelectorAll('[data-donor-row]')) : [];
const donorSearchFields = document.querySelectorAll('[data-donor-search]');
const donorCountryFilter = document.querySelector('[data-donor-country-filter]');
const donorCampaignFilter = document.querySelector('[data-donor-campaign-filter]');
const donorTypeFilter = document.querySelector('[data-donor-type-filter]');
const donorStartDate = document.querySelector('[data-donor-start-date]');
const donorEndDate = document.querySelector('[data-donor-end-date]');
const donorFilterButton = document.querySelector('[data-donor-filter-button]');
const donorPagination = document.querySelector('[data-donor-pagination]');
const donorRange = document.querySelector('[data-donor-range]');
const donorFooterRange = document.querySelector('[data-donor-footer-range]');
const donorEmptyState = document.querySelector('[data-donor-empty]');

if (
    donorRowsContainer
    && donorSearchFields.length > 0
    && donorCountryFilter
    && donorCampaignFilter
    && donorTypeFilter
    && donorStartDate
    && donorEndDate
    && donorPagination
    && donorRange
    && donorFooterRange
    && donorEmptyState
) {
    const donorPageSize = 5;
    let currentDonorPage = 1;
    let filteredDonors = [...donorRows];

    const renderDonorPage = () => {
        const pageCount = Math.ceil(filteredDonors.length / donorPageSize);

        currentDonorPage = Math.min(currentDonorPage, Math.max(pageCount, 1));

        const firstVisibleIndex = filteredDonors.length === 0
            ? 0
            : (currentDonorPage - 1) * donorPageSize;
        const visibleRows = new Set(filteredDonors.slice(firstVisibleIndex, firstVisibleIndex + donorPageSize));

        donorRows.forEach((row) => {
            row.hidden = !visibleRows.has(row);
        });

        const firstResult = filteredDonors.length === 0 ? 0 : firstVisibleIndex + 1;
        const lastResult = Math.min(firstVisibleIndex + donorPageSize, filteredDonors.length);
        const resultCount = filteredDonors.length;

        donorRange.textContent = `Showing ${firstResult}–${lastResult} of ${resultCount} donors`;
        donorFooterRange.textContent = `Showing ${firstResult} to ${lastResult} of ${resultCount} results`;
        donorEmptyState.hidden = resultCount > 0;
        donorPagination.replaceChildren();

        const addPaginationButton = (label, page, isCurrent = false, isDisabled = false) => {
            const button = document.createElement('button');

            button.className = `donation-page-button${isCurrent ? ' is-current' : ''}`;
            button.type = 'button';
            button.textContent = label;
            button.disabled = isDisabled;

            if (isCurrent) {
                button.setAttribute('aria-current', 'page');
            }

            button.addEventListener('click', () => {
                currentDonorPage = page;
                renderDonorPage();
            });

            donorPagination.append(button);
        };

        addPaginationButton('‹', Math.max(currentDonorPage - 1, 1), false, currentDonorPage === 1);

        for (let page = 1; page <= pageCount; page += 1) {
            addPaginationButton(String(page), page, page === currentDonorPage);
        }

        addPaginationButton('›', Math.min(currentDonorPage + 1, pageCount), false, currentDonorPage === pageCount);
    };

    const applyDonorFilters = () => {
        const searchTerm = donorSearchFields[0].value.trim().toLocaleLowerCase();
        const selectedCountry = donorCountryFilter.value;
        const selectedCampaign = donorCampaignFilter.value;
        const selectedType = donorTypeFilter.value;
        const startDate = donorStartDate.value;
        const endDate = donorEndDate.value;

        filteredDonors = donorRows.filter((row) => {
            const matchesSearch = row.dataset.donor?.includes(searchTerm) ?? false;
            const matchesCountry = !selectedCountry || row.dataset.country === selectedCountry;
            const matchesCampaign = !selectedCampaign || row.dataset.campaign === selectedCampaign;
            const matchesType = !selectedType || row.dataset.type === selectedType;
            const matchesStartDate = !startDate || (row.dataset.date ?? '') >= startDate;
            const matchesEndDate = !endDate || (row.dataset.date ?? '') <= endDate;

            return matchesSearch && matchesCountry && matchesCampaign && matchesType && matchesStartDate && matchesEndDate;
        });

        currentDonorPage = 1;
        renderDonorPage();
    };

    donorSearchFields.forEach((searchField) => {
        searchField.addEventListener('input', () => {
            donorSearchFields.forEach((otherSearchField) => {
                if (otherSearchField !== searchField) {
                    otherSearchField.value = searchField.value;
                }
            });

            applyDonorFilters();
        });
    });

    donorFilterButton.addEventListener('click', applyDonorFilters);
    applyDonorFilters();
}
