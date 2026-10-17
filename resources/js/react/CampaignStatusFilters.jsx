import { useCallback, useEffect, useRef, useState } from 'react';

const statuses = [
    { key: '', label: 'All Campaigns', countKey: 'all' },
    { key: 'active', label: 'Active', countKey: 'active' },
    { key: 'completed', label: 'Completed', countKey: 'completed' },
    { key: 'upcoming', label: 'Upcoming', countKey: 'upcoming' },
];

export default function CampaignStatusFilters({
    activeStatus,
    counts,
    search,
    sort,
}) {
    const [selectedStatus, setSelectedStatus] = useState(activeStatus);
    const [campaignCounts, setCampaignCounts] = useState(counts);
    const [currentSearch, setCurrentSearch] = useState(search);
    const [currentSort, setCurrentSort] = useState(sort);
    const [isLoading, setIsLoading] = useState(false);
    const [errorMessage, setErrorMessage] = useState('');
    const requestController = useRef(null);

    const loadCampaignResults = useCallback(async (targetUrl, addHistoryEntry = true) => {
        requestController.current?.abort();
        const controller = new AbortController();
        requestController.current = controller;
        setIsLoading(true);
        setErrorMessage('');

        try {
            const response = await fetch(targetUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html',
                },
                credentials: 'same-origin',
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error(`Campaign results could not be loaded (HTTP ${response.status}).`);
            }

            const responseDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
            const currentListing = document.querySelector('.campaign-listing');
            const responseListing = responseDocument.querySelector('.campaign-listing');
            const currentGrid = currentListing?.querySelector('[data-campaign-list]');
            const responseGrid = responseListing?.querySelector('[data-campaign-list]');
            const currentFilters = document.querySelector('[data-campaign-status-filters]');
            const responseFilters = responseDocument.querySelector('[data-campaign-status-filters]');

            if (!currentListing || !responseListing || !currentGrid || !responseGrid || !responseFilters || !currentFilters) {
                throw new Error('Campaign results could not be updated because the response was incomplete.');
            }

            const nextStatus = responseFilters.dataset.activeStatus;
            const nextCounts = {
                all: Number(responseFilters.dataset.countAll),
                active: Number(responseFilters.dataset.countActive),
                completed: Number(responseFilters.dataset.countCompleted),
                upcoming: Number(responseFilters.dataset.countUpcoming),
            };
            const nextSearch = responseFilters.dataset.search;
            const nextSort = responseFilters.dataset.sort;

            currentGrid.replaceWith(responseGrid);

            currentListing.querySelector('.campaign-empty-state')?.remove();
            const responseEmptyState = responseListing.querySelector('.campaign-empty-state');

            if (responseEmptyState) {
                responseGrid.after(responseEmptyState);
            }

            currentListing.querySelector('.campaign-pagination')?.remove();
            const responsePagination = responseListing.querySelector('.campaign-pagination');

            if (responsePagination) {
                (responseEmptyState || responseGrid).after(responsePagination);
            }

            document.querySelectorAll('[data-campaign-search]').forEach((field) => {
                field.value = nextSearch;
            });
            const sortField = document.querySelector('[data-campaign-sort]');

            if (sortField) {
                sortField.value = nextSort || 'latest';
            }

            document.querySelectorAll('.campaign-query-form, .topbar-search').forEach((form) => {
                ['status', 'sort'].forEach((name) => {
                    let input = form.querySelector(`input[type="hidden"][name="${name}"]`);
                    const value = name === 'status' ? nextStatus : nextSort;

                    if (value) {
                        if (!input) {
                            input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = name;
                            form.append(input);
                        }

                        input.value = value;
                    } else {
                        input?.remove();
                    }
                });
            });

            setSelectedStatus(nextStatus);
            setCampaignCounts(nextCounts);
            setCurrentSearch(nextSearch);
            setCurrentSort(nextSort);

            const nextUrl = new URL(targetUrl, window.location.href);

            if (addHistoryEntry) {
                window.history.pushState({}, '', nextUrl);
            }

            window.dispatchEvent(new CustomEvent('campaign:results-updated', {
                detail: { campaignGrid: responseGrid },
            }));
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Campaign filter request failed.', error);
                setErrorMessage('Could not update campaigns. Please try again.');
            }
        } finally {
            if (requestController.current === controller) {
                requestController.current = null;
                setIsLoading(false);
            }
        }
    }, []);

    useEffect(() => {
        const handlePopState = () => {
            loadCampaignResults(window.location.href, false);
        };

        window.addEventListener('popstate', handlePopState);

        return () => {
            window.removeEventListener('popstate', handlePopState);
            requestController.current?.abort();
        };
    }, [loadCampaignResults]);

    const handleStatusChange = (status) => {
        const url = new URL(window.location.href);

        if (status) {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }

        if (currentSearch) {
            url.searchParams.set('search', currentSearch);
        } else {
            url.searchParams.delete('search');
        }

        if (currentSort) {
            url.searchParams.set('sort', currentSort);
        } else {
            url.searchParams.delete('sort');
        }

        url.searchParams.delete('page');
        loadCampaignResults(url.toString());
    };

    return (
        <>
            {statuses.map(({ key, label, countKey }) => {
                const isSelected = selectedStatus === key;

                return (
                    <button
                        className={`campaign-filter${isSelected ? ' is-selected' : ''}`}
                        type="button"
                        key={countKey}
                        aria-pressed={isSelected}
                        disabled={isLoading}
                        onClick={() => handleStatusChange(key)}
                    >
                        {label} <span>{campaignCounts[countKey]}</span>
                    </button>
                );
            })}
            <span className="visually-hidden" role="status" aria-live="polite">
                {isLoading ? 'Updating campaigns.' : errorMessage}
            </span>
        </>
    );
}
