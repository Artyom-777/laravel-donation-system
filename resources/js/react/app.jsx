
import { useState } from 'react';

export default function DonationWelcome() {
    const [amount, setAmount] = useState(10);

    return (
        <div className="donation-react-demo">
            <h2>Support a Campaign</h2>

            <p>Choose a donation amount:</p>

            {[10, 25, 50, 100].map((value) => (
                <button
                    key={value}
                    type="button"
                    onClick={() => setAmount(value)}
                    aria-pressed={amount === value}
                >
                    ${value}
                </button>
            ))}

            <p>Selected amount: ${amount}</p>
        </div>
    );
}
