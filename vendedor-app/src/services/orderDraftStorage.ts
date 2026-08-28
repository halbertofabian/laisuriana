import type { CartLine, Customer, OrderDetail } from '../types';
import { getSecureValue, removeSecureValue, setSecureValue } from './secureStorage';

export interface OrderDraft {
  version: 1;
  userId: number;
  branchId: number;
  cart: CartLine[];
  customer: Customer;
  notes: string;
  requestId: string;
  editingOrder: OrderDetail | null;
  updatedAt: string;
}

function draftKey(userId: number, branchId: number): string {
  return `order_draft:v1:${userId}:${branchId}`;
}

export async function saveOrderDraft(draft: OrderDraft): Promise<void> {
  await setSecureValue(draftKey(draft.userId, draft.branchId), JSON.stringify(draft));
}

export async function getOrderDraft(userId: number, branchId: number): Promise<OrderDraft | null> {
  const stored = await getSecureValue(draftKey(userId, branchId));
  if (!stored) return null;

  try {
    const draft = JSON.parse(stored) as OrderDraft;
    const valid = draft.version === 1
      && draft.userId === userId
      && draft.branchId === branchId
      && Array.isArray(draft.cart)
      && typeof draft.requestId === 'string'
      && typeof draft.updatedAt === 'string';

    if (valid) return draft;
  } catch {
    // A corrupt or older draft is removed below.
  }

  await removeSecureValue(draftKey(userId, branchId));
  return null;
}

export async function clearOrderDraft(userId: number, branchId: number): Promise<void> {
  await removeSecureValue(draftKey(userId, branchId));
}
