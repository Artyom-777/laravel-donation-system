import React, { useEffect, useRef, useState } from 'react';

export default function AccountMenu({
    csrfToken,
    currentPasswordError,
    newPasswordError,
    passwordUrl,
    statusMessage,
    userName,
}) {
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [isDialogOpen, setIsDialogOpen] = useState(Boolean(currentPasswordError || newPasswordError));
    const menuRef = useRef(null);
    const dialogRef = useRef(null);
    const initial = Array.from(userName)[0]?.toUpperCase() || '?';

    useEffect(() => {
        const dialog = dialogRef.current;

        if (!dialog) {
            return;
        }

        if (isDialogOpen && !dialog.open) {
            dialog.showModal();
        } else if (!isDialogOpen && dialog.open) {
            dialog.close();
        }
    }, [isDialogOpen]);

    useEffect(() => {
        if (!isMenuOpen) {
            return undefined;
        }

        const closeMenuOnOutsideClick = (event) => {
            if (!menuRef.current?.contains(event.target)) {
                setIsMenuOpen(false);
            }
        };
        const closeMenuOnEscape = (event) => {
            if (event.key === 'Escape') {
                setIsMenuOpen(false);
            }
        };

        document.addEventListener('pointerdown', closeMenuOnOutsideClick);
        document.addEventListener('keydown', closeMenuOnEscape);

        return () => {
            document.removeEventListener('pointerdown', closeMenuOnOutsideClick);
            document.removeEventListener('keydown', closeMenuOnEscape);
        };
    }, [isMenuOpen]);

    return (
        <div className="account-menu" ref={menuRef}>
            <button
                className="topbar-user"
                type="button"
                aria-expanded={isMenuOpen}
                aria-controls="account-menu-dropdown"
                onClick={() => setIsMenuOpen((open) => !open)}
            >
                <span className="topbar-avatar" aria-hidden="true">{initial}</span>
                <span>{userName}</span>
                <svg className="chevron-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
            </button>

            <div className="account-menu-dropdown" id="account-menu-dropdown" hidden={!isMenuOpen}>
                <button
                    className="account-menu-item"
                    type="button"
                    onClick={() => {
                        setIsMenuOpen(false);
                        setIsDialogOpen(true);
                    }}
                >
                    Change password
                </button>
            </div>

            <dialog
                className="password-change-dialog"
                ref={dialogRef}
                aria-labelledby="password-change-title"
                onClose={() => setIsDialogOpen(false)}
            >
                <div className="password-change-dialog-content">
                    <button
                        className="password-dialog-close"
                        type="button"
                        aria-label="Close password change dialog"
                        onClick={() => setIsDialogOpen(false)}
                    >
                        &times;
                    </button>
                    <p className="password-dialog-eyebrow">Account security</p>
                    <h2 id="password-change-title">Change password</h2>
                    <p className="password-dialog-description">Enter your current password and choose a new one.</p>

                    <form method="POST" action={passwordUrl}>
                        <input type="hidden" name="_token" value={csrfToken} />

                        <label className="password-field">
                            <span>Current password</span>
                            <input
                                type="password"
                                name="current_password"
                                autoComplete="current-password"
                                required
                                aria-invalid={Boolean(currentPasswordError)}
                                aria-describedby={currentPasswordError ? 'current-password-error' : undefined}
                            />
                            {currentPasswordError && (
                                <span className="password-field-error" id="current-password-error" role="alert">
                                    {currentPasswordError}
                                </span>
                            )}
                        </label>

                        <label className="password-field">
                            <span>New password</span>
                            <input
                                type="password"
                                name="password"
                                autoComplete="new-password"
                                minLength="8"
                                required
                                aria-invalid={Boolean(newPasswordError)}
                                aria-describedby={newPasswordError ? 'new-password-error' : undefined}
                            />
                            {newPasswordError && (
                                <span className="password-field-error" id="new-password-error" role="alert">
                                    {newPasswordError}
                                </span>
                            )}
                        </label>

                        <label className="password-field">
                            <span>Confirm new password</span>
                            <input
                                type="password"
                                name="password_confirmation"
                                autoComplete="new-password"
                                minLength="8"
                                required
                            />
                        </label>

                        <div className="password-dialog-actions">
                            <button className="password-cancel-button" type="button" onClick={() => setIsDialogOpen(false)}>
                                Cancel
                            </button>
                            <button className="password-submit-button" type="submit">
                                Update password
                            </button>
                        </div>
                    </form>
                </div>
            </dialog>

            {statusMessage && <p className="account-password-status" role="status">{statusMessage}</p>}
        </div>
    );
}
