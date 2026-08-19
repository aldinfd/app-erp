/**
 * Bentuk props modul Finance (plan Phase 6) — jurnal umum, buku besar,
 * dan laporan keuangan. Uang tetap string/number; format tampilan di FE.
 */

export type JournalSource = 'sales_payment' | 'purchase_received' | 'purchase_payment' | 'manual';

export type JournalAccountOption = {
    id: number;
    code: string;
    name: string;
};

export type JournalLine = {
    id: number;
    account_id: number;
    debit: string;
    credit: string;
    account?: JournalAccountOption | null;
};

export type JournalEntry = {
    id: number;
    entry_number: string;
    entry_date: string;
    description: string;
    source: JournalSource;
    reference_type: string | null;
    reference_id: number | null;
    posted_by: number | null;
    poster?: { id: number; name: string } | null;
    lines?: JournalLine[];
};

export type PaginatedJournalEntries = {
    data: JournalEntry[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type LedgerLine = {
    entry_number: string;
    entry_date: string;
    description: string;
    debit: number;
    credit: number;
    balance: number;
};

export type Ledger = {
    opening: number;
    closing: number;
    total_debit: number;
    total_credit: number;
    lines: LedgerLine[];
};

export type LedgerAccount = {
    id: number;
    code: string;
    name: string;
    type: string;
};

export type ReportRow = {
    code: string;
    name: string;
    amount: number;
};

export type IncomeStatement = {
    from: string;
    to: string;
    revenues: ReportRow[];
    expenses: ReportRow[];
    total_revenue: number;
    total_expense: number;
    net_income: number;
};

export type BalanceSheet = {
    as_of: string;
    assets: ReportRow[];
    liabilities: ReportRow[];
    equity: ReportRow[];
    current_earnings: number;
    total_assets: number;
    total_liabilities: number;
    total_equity: number;
};
