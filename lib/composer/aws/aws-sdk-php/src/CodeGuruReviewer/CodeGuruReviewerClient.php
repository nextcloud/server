<?php
namespace Aws\CodeGuruReviewer;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon CodeGuru Reviewer** service.
 * @method \Aws\Result associateRepository(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateRepositoryAsync(array $args = [])
 * @method \Aws\Result createCodeReview(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createCodeReviewAsync(array $args = [])
 * @method \Aws\Result describeCodeReview(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeCodeReviewAsync(array $args = [])
 * @method \Aws\Result describeRecommendationFeedback(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeRecommendationFeedbackAsync(array $args = [])
 * @method \Aws\Result describeRepositoryAssociation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeRepositoryAssociationAsync(array $args = [])
 * @method \Aws\Result disassociateRepository(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateRepositoryAsync(array $args = [])
 * @method \Aws\Result listCodeReviews(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listCodeReviewsAsync(array $args = [])
 * @method \Aws\Result listRecommendationFeedback(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listRecommendationFeedbackAsync(array $args = [])
 * @method \Aws\Result listRecommendations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listRecommendationsAsync(array $args = [])
 * @method \Aws\Result listRepositoryAssociations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listRepositoryAssociationsAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result putRecommendationFeedback(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putRecommendationFeedbackAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 */
class CodeGuruReviewerClient extends AwsClient {}
