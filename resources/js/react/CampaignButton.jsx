import { useEffect, useRef, useState } from 'react';

const campaignStatusMessages = {
    active: 'This campaign is accepting donations.',
    completed: 'This campaign is completed. Donations are now closed.',
    upcoming: 'This campaign has not started yet.',
};

export default function CampaignButton({
    action,
    campaignDescription,
    campaignId,
    campaignStatus,
    campaignTitle,
    className,
    confirmMessage,
    disabled,
    expanded,
    label,
    panelId,
    type,
}) {
    const [isExpanded, setIsExpanded] = useState(expanded === 'true');
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const dialogRef = useRef(null);
    const titleId = `campaign-dialog-title-${campaignId}`;

    useEffect(() => {
        if (action !== 'donate') {
            return undefined;
        }

        const closeOtherDonationPanel = (event) => {
            if (event.detail?.campaignId === campaignId) {
                return;
            }

            const panel = document.getElementById(panelId);

            if (panel) {
                panel.hidden = true;
                setIsExpanded(false);
            }
        };

        window.addEventListener('campaign:donation-panel-open', closeOtherDonationPanel);

        return () => {
            window.removeEventListener('campaign:donation-panel-open', closeOtherDonationPanel);
        };
    }, [action, campaignId, panelId]);

    useEffect(() => {
        const dialog = dialogRef.current;

        if (isDialogOpen && dialog && !dialog.open) {
            dialog.showModal();
        } else if (!isDialogOpen && dialog?.open) {
            dialog.close();
        }
    }, [isDialogOpen]);

    const handleClick = (event) => {
        if (action === 'donate') {
            const panel = document.getElementById(panelId);

            if (!panel) {
                console.error(`Campaign donation form panel "${panelId}" was not found.`);

                return;
            }

            const nextExpanded = !isExpanded;

            panel.hidden = !nextExpanded;
            setIsExpanded(nextExpanded);

            if (nextExpanded) {
                window.dispatchEvent(new CustomEvent('campaign:donation-panel-open', {
                    detail: { campaignId },
                }));
                panel.dispatchEvent(new Event('campaign:donation-open', { bubbles: true }));
            }
        }

        if (action === 'details') {
            setIsDialogOpen(true);
        }

        if (action === 'delete' && !window.confirm(confirmMessage)) {
            event.preventDefault();
        }
    };

    return (
        <>
            <button
                className={className}
                type={type}
                disabled={disabled}
                aria-expanded={action === 'donate' ? isExpanded : undefined}
                aria-controls={action === 'donate' ? panelId : undefined}
                aria-haspopup={action === 'details' ? 'dialog' : undefined}
                onClick={handleClick}
            >
                {label}
            </button>
            {action === 'details' && isDialogOpen && (
                <dialog
                    className="campaign-details-dialog"
                    aria-labelledby={titleId}
                    ref={dialogRef}
                    onClose={() => setIsDialogOpen(false)}
                    onClick={(event) => {
                        if (event.target === event.currentTarget) {
                            setIsDialogOpen(false);
                        }
                    }}
                >
                    <div className="campaign-details-dialog-content">
                        <p className="campaign-dialog-status">{campaignStatusMessages[campaignStatus] || ''}</p>
                        <h2 id={titleId}>{campaignTitle}</h2>
                        <p className="campaign-dialog-description">{campaignDescription}</p>
                        <form method="dialog">
                            <button className="campaign-action-button campaign-primary-button" type="submit">
                                Close
                            </button>
                        </form>
                    </div>
                </dialog>
            )}
        </>
    );
}
