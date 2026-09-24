<div class="container-fluid testimonial py-5">
    <div class="container py-5">
        @php $homePage = is_array($homePage ?? null) ? $homePage : []; @endphp
        <div class="ag-testimonial-header text-center mx-auto mb-5 wow fadeIn" data-wow-delay="0.1s">
            <p class="fs-5 text-uppercase text-primary">{{ $homePage['testimonials_eyebrow'] ?? 'Testimonials' }}</p>
            <h2 class="display-3 mb-0">Testimonies</h2>
            <p class="text-muted mt-3 mb-0">Real grace, healing, and transformation in Christ.</p>
            <button type="button" class="btn btn-primary btn-lg px-5 py-3 mt-4 ag-btn-write-testimony" data-bs-toggle="modal" data-bs-target="#agTestimonyModal">
                <i class="fas fa-pen-fancy me-2"></i>Write Your Testimony
            </button>
        </div>
        <div class="testimonial-carousel-wrap wow fadeIn" data-wow-delay="0.15s">
            <div class="owl-carousel testimonial-carousel" data-testimony-source="index">
                <div class="testimonial-item testimonial-item--loading">
                    <div class="testimonial-content text-center py-5">
                        <p class="text-muted mb-0"><i class="fas fa-spinner fa-spin me-2"></i>Loading approved testimonies…</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
