"use client";

import React, { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Plus, Trash2, Calculator } from "lucide-react";
import type { BudgetItem } from "@/lib/supabase/types";

interface BudgetBuilderProps {
  initialItems?: BudgetItem[];
  readOnly?: boolean;
}

const defaultCategories: BudgetItem["category"][] = [
  "materials",
  "food",
  "honorarium",
  "transportation",
  "equipment",
  "other",
];

export function BudgetBuilder({ initialItems = [], readOnly = false }: BudgetBuilderProps) {
  const [items, setItems] = useState<BudgetItem[]>(() => {
    if (initialItems.length > 0) return initialItems;
    if (readOnly) return [];
    return [
      {
        id: "item-1",
        description: "Event Materials / Certificates",
        quantity: 1,
        unit_cost: 500,
        total_cost: 500,
        category: "materials",
      },
    ];
  });

  const addItem = () => {
    const newItem: BudgetItem = {
      id: "item-" + Math.random().toString(36).substring(2, 9),
      description: "",
      quantity: 1,
      unit_cost: 0,
      total_cost: 0,
      category: "materials",
    };
    setItems([...items, newItem]);
  };

  const removeItem = (id: string) => {
    setItems(items.filter((item) => item.id !== id));
  };

  const updateItem = (id: string, field: keyof BudgetItem, value: any) => {
    setItems(
      items.map((item) => {
        if (item.id === id) {
          const updated = { ...item, [field]: value };
          if (field === "quantity" || field === "unit_cost") {
            const qty = Number(field === "quantity" ? value : item.quantity) || 0;
            const cost = Number(field === "unit_cost" ? value : item.unit_cost) || 0;
            updated.total_cost = qty * cost;
          }
          return updated;
        }
        return item;
      })
    );
  };

  const grandTotal = items.reduce((sum, item) => sum + (Number(item.total_cost) || 0), 0);

  return (
    <div className="space-y-4">
      {/* Hidden input storing the JSON stringified budget array for form submission */}
      <input type="hidden" name="budget_items" value={JSON.stringify(items)} />

      <div className="border rounded-xl overflow-hidden bg-card shadow-sm">
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="text-xs uppercase bg-muted/50 border-b text-muted-foreground">
              <tr>
                <th className="px-4 py-3 min-w-[200px]">Item Description</th>
                <th className="px-4 py-3 min-w-[140px]">Category</th>
                <th className="px-4 py-3 w-28 text-center">Qty</th>
                <th className="px-4 py-3 w-36 text-right">Unit Cost (₱)</th>
                <th className="px-4 py-3 w-36 text-right">Subtotal (₱)</th>
                {!readOnly && <th className="px-4 py-3 w-16 text-center">Action</th>}
              </tr>
            </thead>
            <tbody className="divide-y divide-border/50">
              {items.length === 0 ? (
                <tr>
                  <td
                    colSpan={readOnly ? 5 : 6}
                    className="px-4 py-8 text-center text-muted-foreground text-sm"
                  >
                    No budget items allocated.
                  </td>
                </tr>
              ) : (
                items.map((item) => (
                  <tr key={item.id} className="hover:bg-muted/20 transition-colors">
                    <td className="p-2 sm:px-4">
                      {readOnly ? (
                        <span className="font-medium text-foreground">{item.description}</span>
                      ) : (
                        <Input
                          value={item.description}
                          onChange={(e) => updateItem(item.id, "description", e.target.value)}
                          placeholder="e.g. Certificate Printing, Sound System"
                          className="h-8 text-sm"
                          required
                        />
                      )}
                    </td>
                    <td className="p-2 sm:px-4">
                      {readOnly ? (
                        <span className="capitalize text-muted-foreground text-xs font-semibold px-2 py-1 bg-muted rounded">
                          {item.category}
                        </span>
                      ) : (
                        <select
                          value={item.category}
                          onChange={(e) => updateItem(item.id, "category", e.target.value)}
                          className="h-8 rounded-md border border-input bg-background px-2 text-xs w-full focus:outline-none focus:ring-1 focus:ring-ring capitalize"
                        >
                          {defaultCategories.map((cat) => (
                            <option key={cat} value={cat}>
                              {cat}
                            </option>
                          ))}
                        </select>
                      )}
                    </td>
                    <td className="p-2 sm:px-4 text-center">
                      {readOnly ? (
                        <span className="font-medium">{item.quantity}</span>
                      ) : (
                        <Input
                          type="number"
                          min="1"
                          value={item.quantity}
                          onChange={(e) =>
                            updateItem(item.id, "quantity", Math.max(1, parseInt(e.target.value) || 1))
                          }
                          className="h-8 text-center text-sm w-20 mx-auto"
                          required
                        />
                      )}
                    </td>
                    <td className="p-2 sm:px-4 text-right">
                      {readOnly ? (
                        <span>₱{Number(item.unit_cost).toLocaleString("en-PH", { minimumFractionDigits: 2 })}</span>
                      ) : (
                        <Input
                          type="number"
                          min="0"
                          step="0.01"
                          value={item.unit_cost}
                          onChange={(e) =>
                            updateItem(item.id, "unit_cost", parseFloat(e.target.value) || 0)
                          }
                          className="h-8 text-right text-sm w-32 ml-auto"
                          required
                        />
                      )}
                    </td>
                    <td className="p-2 sm:px-4 text-right font-semibold text-foreground">
                      ₱{(Number(item.total_cost) || 0).toLocaleString("en-PH", { minimumFractionDigits: 2 })}
                    </td>
                    {!readOnly && (
                      <td className="p-2 sm:px-4 text-center">
                        <Button
                          type="button"
                          variant="ghost"
                          size="icon"
                          onClick={() => removeItem(item.id)}
                          className="h-7 w-7 text-muted-foreground hover:text-destructive"
                          title="Remove item"
                        >
                          <Trash2 className="w-4 h-4" />
                        </Button>
                      </td>
                    )}
                  </tr>
                ))
              )}
            </tbody>
            <tfoot className="border-t bg-muted/30 font-semibold">
              <tr>
                <td colSpan={3} className="px-4 py-3">
                  {!readOnly && (
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={addItem}
                      className="text-xs h-8 border-dashed"
                    >
                      <Plus className="w-3.5 h-3.5 mr-1 text-emerald-700" />
                      Add Budget Line Item
                    </Button>
                  )}
                </td>
                <td className="px-4 py-3 text-right text-muted-foreground text-xs uppercase tracking-wider">
                  Grand Total Budget:
                </td>
                <td className="px-4 py-3 text-right text-base font-bold text-emerald-800 dark:text-emerald-400">
                  ₱{grandTotal.toLocaleString("en-PH", { minimumFractionDigits: 2 })}
                </td>
                {!readOnly && <td />}
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  );
}
