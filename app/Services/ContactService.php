<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ContactService
{
    public function deleteContact($contact)
    {
        abort_if($contact->is_default, 403, 'This contact is default.');

        DB::transaction(function () use ($contact) {
            Transaction::where('user_id', auth()->id())
                ->where('contact_id', $contact->id)
                ->update(['contact_id' => null]);

            $contact->delete();
        });
    }

    /**
     * @param  array  $data
     * @return Contact
     */
    public function getOrCreateCustomer($isNew, $userId, $customerId = null, $data = [])
    {
        if (! $isNew) {
            return Contact::where([
                'id' => $customerId,
                'user_id' => $userId,
            ])->firstOrFail();
        }

        $contact = Contact::firstOrNew([
            'user_id' => $userId,
            'phone_number' => $data['phone_number'],
        ]);
        $contact->name = $data['name'];
        $contact->contact_type = $data['contact_type'];
        $contact->address = $data['address'] ?? $contact->address;
        $contact->save();

        return $contact;
    }

    /**
     * @return mixed
     */
    public function updateContact($userId, $data)
    {
        $contact = Contact::firstOrNew([
            'user_id' => $userId,
            'phone_number' => $data['phone_number'],
        ]);
        $contact->name = $data['name'];
        $contact->contact_type = $data['contact_type'];
        $contact->address = $data['address'] ?? $contact->address;
        abort_if($contact->isClean(), 422, 'No changes to update.');
        $contact->save();

        return $contact;
    }
}
