<x-guest-layout title="Sign in">
    <div class="mx-auto flex min-h-screen w-full max-w-[1080px] flex-col justify-center px-4 py-10 sm:px-6 lg:grid lg:grid-cols-[1fr_420px] lg:items-center lg:gap-20">
        <section class="mb-10 flex flex-col lg:mb-0">
            <p class="text-sm font-semibold">School Manager</p>
            <h1 class="mt-4 max-w-xl text-2xl font-semibold tracking-tight">Sign in to your school</h1>
            <p class="mt-4 max-w-lg text-sm text-muted">
                Use the account issued by your school. Access is limited to the schools linked to your account.
            </p>
            <p class="mt-auto max-w-lg pt-12 text-[13px] text-muted">
                Internal school tool · Self-hosted · No third-party scripts or fonts
            </p>
        </section>

        <section class="fs-card self-start p-6">
            <h2 class="text-base font-medium">Account</h2>
            <p class="mt-1 text-[13px] text-muted">Enter your email and password.</p>

            <div data-api-error role="alert" hidden
                 class="mt-4 rounded-md border border-negative/30 bg-negative/5 px-3 py-2 text-[13px] text-negative"></div>

            <form data-api-login class="mt-5 space-y-4" novalidate>
                <x-input name="email" label="Email address" type="email" autocomplete="username" required />
                <x-input name="password" label="Password" type="password" autocomplete="current-password" required />
                <x-button type="submit" class="w-full">Sign in</x-button>
            </form>
        </section>
    </div>
</x-guest-layout>
